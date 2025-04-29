<?php

class MolcomOrderXmlBuilder extends waJsonController
{
    public static function convertPhoneToMolcomFormat($phone = '')
    {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return false;
        }
        // Убираем все лишние символы
        $phone = preg_replace('/[^\d]/', '', $phone);
        // Если начинается на 8 и далее 9 цифр, исправляем на +7
        if (preg_match('/^8[49]\d{9}$/', $phone)) {
            $phone = '7' . substr($phone, 1);
        }
        // Добавляем плюс в начале
        $phone = '+' . $phone;
        // Валидируем итоговый формат
        if (preg_match('/^\+79\d{9}$/', $phone)) {
            return $phone;
        }
        return $phone;
    }
    public static function build($order, $settings)
    {
        $order_id = $order['id'];

        // $shipping_name = $order['params']['shipping_name'] ?? '';
        // $payment_name  = $order['params']['payment_name'] ?? '';


        // $order_model = new shopOrderModel();
        // $order = $order_model->getById($order_id);

        // Fetch contact details
        $contact = new waContact($order['contact_id']);
        $order['contact'] = [
            'name' => $contact->get('name'),
            'lastname' => $contact->get('lastname'),
            'email' => $contact->get('email'),
            'phone' => $contact->get('phone'),
        ];

        $order_items_model = new shopOrderItemsModel();
        $order['items'] = $order_items_model->getByField('order_id', $order_id, true);

        // Fetch product details including image_id
        foreach ($order['items'] as &$item) {
            $product_skus_model = new shopProductSkusModel();
            $sku = $product_skus_model->getById($item['sku_id']);
            if ($sku) {
                $product = new shopProduct($sku['product_id']);
                $images = $product->getImages([
                    'aux' => '200',
                ]);
            }
        }

        $plugin = wa('shop')->getPlugin('molcom');

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><ORD></ORD>');
        $xml->addChild('FILE_ID', 'InternetSale_'.$order_id.'_'.date('YmdHis'));

        $header = $xml->addChild('HEADER');
        $header->addChild('OWNERCODE', $settings['owner_code']);
        $header->addChild('OWNERINN', $settings['owner_inn']);
        $header->addChild('INVOICE', $order_id);
        $header->addChild('DATE', date('d.m.Y'));
        $header->addChild('ORDER_TYPE', '1');
        $header->addChild('EST_SHIP_DATE', date('d.m.Y', strtotime('+1days')));
        $header->addChild('SHIPMENT_METHOD', 'DPD');
        $header->addChild('DeliveryService', '');
        $header->addChild('AmountwVAT', round(($order['total'] - $order['shipping'] + $order['discount']), 2));
        $header->addChild('VATpercent', 20); //
        $header->addChild('AmountVAT', round(($order['total'] - $order['shipping'])*0.1666666666666667, 2));
        $header->addChild('TotalDiscount', round($order['discount'], 2));
        $header->addChild('AmountShipwoVAT', round($order['shipping'] - $order['shipping']*0.1666666666666667, 2));
        $header->addChild('AmountShipVAT', round($order['shipping']*0.1666666666666667, 2));
        $header->addChild('AmountShipwVAT', round($order['shipping'], 2));
        $header->addChild('ShipDiscount', 0);
        $header->addChild('orderPaymentType', '');

        if (isset($order['contact']['phone']) && !empty($order['contact']['phone'])) {
            if ($order_contact_phone = self::convertPhoneToMolcomFormat($order['contact']['phone'][0]['value'])) {
                $data['Phone'] = $order_contact_phone;
                $header->addChild('Phone', $order_contact_phone);
            }
        }

        $header->addChild('COMMENT', isset($order['comment']) && !empty($order['comment']) ? strip_tags($order['comment']) : '');
        $header->addChild('HasDanger', 0);
        $header->addChild('LastName', $order['contact']['lastname']);
        $header->addChild('Name', $order['contact']['name']);
        $header->addChild('MiddleName', '');
        $header->addChild('Email', $order['contact']['email'][0]['value']);

        if (isset($order['params']['shipping_address.country']) && !empty($order['params']['shipping_address.country'])) {
            $header->addChild('Country', $order['params']['shipping_address.country'] == 'rus' ? 'Россия' : $order['params']['shipping_address.country']);
        }

//            $header->addChild('Index', $order['shipping_address']['zip']);

        if (isset($order['params']['shipping_address.region']) && !empty($order['params']['shipping_address.region'])) {
            $header->addChild('Region', $order['params']['shipping_address.region']);
        }
        if (isset($order['params']['shipping_address.city']) && !empty($order['params']['shipping_address.city'])) {
            $header->addChild('City', $order['params']['shipping_address.city']);
        }

        $header->addChild('Street', $order['shipping_address']['street'] ?? '');
        $header->addChild('House', $order['shipping_address']['house'] ?? '');
        $header->addChild('Building', '');
        $header->addChild('Flat', $order['shipping_address']['room'] ?? '');
        $header->addChild('PickPoint_Code', '');

        if (isset($order['roistat_visit']) && !empty($order['roistat_visit'])) {
            $header->addChild('roistat', $order['roistat_visit']);
        }


        $details = $xml->addChild('DETAILS');
        $n = 1;
        foreach ($order['items'] as $item) {
            $detail = $details->addChild('DETAIL');
            $detail->addChild('N_STR', $n++);
            $detail->addChild('STOCK_NUMBER', $item['sku_code']);
            $detail->addChild('STOCK_NAME', htmlspecialchars($item['name']));
            $detail->addChild('MeasureUnit', 'ШТ');
            $detail->addChild('UnitPrice', round($item['price'] - ($item['price']*0.1666666666666667), 2));

            $order_item_nds_price = round($item['price']*0.1666666666666667, 2);
            $detail->addChild('UnitVAT', $order_item_nds_price);

            $detail->addChild('PRICE', round($item['price'], 2));
            $detail->addChild('LinePrice', round(($item['price'] - ($item['price']*0.1666666666666667))*$item['quantity'], 2));
            $detail->addChild('LineVAT', round(($item['price']*0.1666666666666667)*$item['quantity'], 2));
            $detail->addChild('SUM', round($item['price']*$item['quantity'], 2));
            $detail->addChild('LineDiscount', round($item['total_discount'], 2));
            $detail->addChild('QTYEXPECTED', $item['quantity']);
            $detail->addChild('LineAmount', round(($item['price']*$item['quantity'] -$item['total_discount']), 2));
        }

        return $xml->asXML();
    }
}