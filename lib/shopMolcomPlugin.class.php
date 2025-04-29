<?php

class shopMolcomPlugin extends shopPlugin
{
    public function backendOrder($params)
    {
        $order_id = $params['id'];

        // Получаем параметры заказа
        $order_params_model = new shopOrderParamsModel();
        $params_order = $order_params_model->get($order_id);

        $is_exported = !empty($params_order['molcom_exported']);

        $disabled = $is_exported ? 'disabled="disabled"' : '';
        $button_text = $is_exported ? 'Уже отправлен в Molcom' : _wp('Отправить в Molcom');

        $html = '<span id="' . $this->id . '-action-button" class="button send-to-molcom-link" data-order-id="' . $order_id . '" ' . $disabled . '><i class="fa fa-key text-blue"></i> ' . $button_text . '</span>';

        // JavaScript для AJAX-запроса
        $html .= '
        <script>
            $(function() {
                $(document).off("click.molcom").on("click.molcom", ".send-to-molcom-link:not([disabled])", function() {
                    var orderId = $(this).data("order-id");
                    $.post("?plugin=molcom&action=sendOrder", {order_id: orderId}, function(response) {
                        if (response.status === "ok") {
                            alert("Заказ успешно отправлен в МОЛКОМ");
                            $(".send-to-molcom-link[data-order-id=\'" + orderId + "\']").attr("disabled", "disabled").text("Уже отправлен в Molcom");
                        } else if (response.message) {
                            alert(response.message);
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
        $settings = $this->getSettings();
        MolcomOrderExporter::export($params['order_id'], $settings);
    }

    public function orderActionPay($params)
    {
        $settings = $this->getSettings();
        MolcomOrderExporter::export($params['order_id'], $settings);
    }

}