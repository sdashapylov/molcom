<?php

class MolcomOrderExporter
{
    /**
     * Экспортирует заказ в Molcom, если он ещё не был выгружен.
     * @param int $order_id
     * @param array $settings
     * @return array ['status' => 'ok'|'error', 'message' => string]
     */
    public static function export($order_id, $settings)
    {
        $order_model = new shopOrderModel();
        $order = $order_model->getOrder($order_id);

        if (!$order) {
            waLog::log("Error: Order not found for order_id " . $order_id, 'molcom.log');
            return ['status' => 'error', 'message' => 'Заказ не найден'];
        }

        $order_params_model = new shopOrderParamsModel();
        $params = $order_params_model->get($order_id);

        if (!empty($params['molcom_exported'])) {
            waLog::log("Molcom: заказ $order_id уже выгружен, повтор не требуется", 'molcom.log');
            return ['status' => 'error', 'message' => 'Заказ уже был отправлен в Molcom ранее'];
        }

        // 1. Генерируем XML
        $xml_content = MolcomOrderXmlBuilder::build($order, $settings);

        // 2. Сохраняем временно (если нужно)
        $filename = 'InternetSale_'.$order_id.'_'.date('YmdHis').'.xml';

        // 3. Отправляем на SFTP
        $sftp_sender = new MolcomSftpSender(
            $settings['host'],
            $settings['user'],
            $settings['password'],
            $settings['save_path']
        );

        try {
            $sftp_sender->send($filename, $xml_content);
            $order_params_model->setOne($order_id, 'molcom_exported', date('Y-m-d H:i:s'));
            waLog::log("✅ Успешно отправлен на SFTP: $filename", 'molcom.log');
            return ['status' => 'ok', 'message' => 'Успешно отправлен в Molcom'];
        } catch (Exception $e) {
            waLog::log("❌ SFTP ошибка: " . $e->getMessage(), 'molcom.log');
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
