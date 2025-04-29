<?php
class shopMolcomPluginBackendSendOrderController extends waJsonController
{
    public function execute()
    {
        $order_id = waRequest::post('order_id', null, waRequest::TYPE_INT);
        $plugin = wa('shop')->getPlugin('molcom');
        $settings = $plugin->getSettings();

        // Получаем параметры заказа!
        $order_params_model = new shopOrderParamsModel();
        $params = $order_params_model->get($order_id);

        if (!empty($params['molcom_exported'])) {
            $this->response = [
                'status' => 'error',
                'message' => 'Заказ уже был отправлен в Molcom ранее'
            ];
            return;
        }

        $result = MolcomOrderExporter::export($order_id, $settings);

        // ВАЖНО: возвращаем только status и message!
        $this->response = [
            'status' => $result['status'],
            'message' => $result['message']
        ];
    }
}