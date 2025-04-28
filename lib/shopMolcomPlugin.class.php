<?php

class shopMolcomPlugin extends shopPlugin
{
    public function backendOrder($params)
    {
        $order_id = $params['id'];

        $html = '<span id="' . $this->id . '-action-button" class="button send-to-keycrm-link" data-order-id="' . $order_id . '"><i class="fa fa-key text-blue"></i> ' . _wp('Отправить в Molcom') . '</span>';

        // JavaScript для AJAX-запроса
        $html .= '
        <script>
            $(document).ready(function() {
				$(document).off("click", ".send-to-keycrm-link");
				
                $(document).on("click", ".send-to-keycrm-link", function() {
                    var orderId = $(this).data("order-id");
                    $.post("?plugin=molcom&action=sendOrder", {order_id: orderId}, function(response) {
                        if (response.status === "ok") {
                            alert("Заказ успешно отправлен в МОЛКОМ");
                        } else {
                            alert("Ошибка при отправке");
                        }
                    }, "json");
                });
            });
        </script>';

        return [
            'action_button' => $html,
        ];
    }

    public function orderActionProcess($params)
    {
        $this->exportOrderToMolcom($params['order_id']);
    }

    public function orderActionPay($params)
    {
        $this->exportOrderToMolcom($params['order_id']);
    }

    private function exportOrderToMolcom($order_id)
    {
        $order_model = new shopOrderModel();
        $order = $order_model->getById($order_id);

        // Получаем параметры заказа
        $order_params_model = new shopOrderParamsModel();
        $params = $order_params_model->get($order_id);

        // Проверяем, был ли уже выгружен заказ
        if (!empty($params['molcom_exported'])) {
            waLog::log("Molcom: заказ $order_id уже выгружен, повтор не требуется", 'molcom.log');
            return;
        }

        if ($order) {
            // 1. Генерируем XML
            $settings = $this->getSettings();
            $xml_content = MolcomOrderXmlBuilder::build($order, $settings);

            // 2. Сохраняем временно (если нужно)
            $filename = 'InternetSale_'.$order_id.'_'.date('YmdHis').'.xml';

            // 3. Отправляем на SFTP
            $sftp_sender = new MolcomSftpSender(
                $this->getSettings('host'),
                $this->getSettings('user'),
                $this->getSettings('password'),
                $this->getSettings('save_path')
            );

            try {
                $sftp_sender->send($filename, $xml_content);
                waLog::log("✅ Успешно отправлен на SFTP: $filename", 'molcom.log');
            } catch (Exception $e) {
                waLog::log("❌ SFTP ошибка: " . $e->getMessage(), 'molcom.log');
            }

            // После успешной отправки:
            $order_params_model->set($order_id, [
                'molcom_exported' => date('Y-m-d H:i:s')
            ]);
        } else {
            waLog::log("Error: Order not found for order_id " . $order_id, 'keycrm.log');
        }
    }
}
