<?php
class shopMolcomPluginBackendSendOrderController extends waJsonController
{
    public function execute()
    {
        $order_id = waRequest::post('order_id', null, waRequest::TYPE_INT);
        $order_model = new shopOrderModel();
        $order = $order_model->getOrder($order_id);

        if ($order) {
            // Проверка на дубль
            $order_params_model = new shopOrderParamsModel();
            $params = $order_params_model->get($order_id);
            if (!empty($params['molcom_exported'])) {
                $this->response = [
                    'status' => 'error',
                    'message' => 'Заказ уже был отправлен в Molcom ранее'
                ];
                return;
            }

            // Получаем настройки плагина
            $plugin = wa('shop')->getPlugin('molcom');
            $settings = $plugin->getSettings();

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
                // Ставим флаг после успешной отправки
                $order_params_model->set($order_id, [
                    'molcom_exported' => date('Y-m-d H:i:s')
                ]);
                $this->response = ['status' => 'ok'];
                waLog::log("✅ Успешно отправлен на SFTP: $filename", 'molcom.log');
            } catch (Exception $e) {
                $this->response = [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
                waLog::log("❌ SFTP ошибка: " . $e->getMessage(), 'molcom.log');
            }

        } else {
            $this->response = [
                'status' => 'error',
                'message' => 'Заказ не найден'
            ];
            waLog::log("Error: Order not found for order_id " . $order_id, 'keycrm.log');
        }
    }
}