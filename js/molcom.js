$(document).ready(function() {
	$(document).on("click", ".send-to-molcom-link", function() {
		var orderId = $(this).data("order-id");
		$.post("?plugin=molcom&action=sendOrder", {order_id: orderId}, function(response) {
			if (response.status === "ok") {
				alert("Заказ успешно отправлен в Molcom");
			} else {
				alert("Ошибка при отправке");
			}
		}, "json");
	});
});