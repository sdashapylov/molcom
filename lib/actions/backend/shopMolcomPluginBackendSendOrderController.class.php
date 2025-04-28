<?php
class shopMolcomPluginBackendSendOrderController extends waJsonController
{
    public function execute()
    {
        $order_id = waRequest::post('order_id', null, waRequest::TYPE_INT);
        $order_model = new shopOrderModel();
        $order = $order_model->getOrder($order_id);

        if ($order) {
            // 1. Генерируем XML
            $plugin = wa('shop')->getPlugin('molcom');
            $settings = $plugin->getSettings(); 

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
                waLog::log("✅ Успешно отправлен на SFTP: $filename", 'molcom.log');
            } catch (Exception $e) {
                waLog::log("❌ SFTP ошибка: " . $e->getMessage(), 'molcom.log');
            }

        } else {
            waLog::log("Error: Order not found for order_id " . $order_id, 'keycrm.log');
        }
    }
}