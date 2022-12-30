</body>
<!-- FOOTER: DEBUG INFO + COPYRIGHTS -->
<foot>
	<div class="copyrights">
	</div>
</foot>

<!-- SCRIPTS -->
<script>
	'use strict';
	var cmjs_obj = {
		init_load: function() {

			
			

			//sns login gg
			$(document).on("click", ".btn_google_login", function(event) {
				var self = this;
				location.href = "/oauth/sns_request_post?type=GG";
			});
			

			$(document).on("click", ".btn_apple_login", function(event) {
				var self = this;
				location.href = "/oauth/sns_request_post?type=AP";
			});

			$(document).on("click", ".btn_facebook_login", function(event) {
				var self = this;
				location.href = "/oauth/sns_request_post?type=FB";
			});

			//add product
			$(document).on("click", ".pluscircle", function(event) {
				var self = this;
				location.href = "/product/product_add";
			});
			
			//home
			$(document).on("click", ".btn_home", function(event) {
				var self = this;
				location.href = "/";
			});

			//chat
			$(document).on("click", ".btn_chat", function(event) {
				var self = this;
				location.href = "/chat/offer_view_list";
			});

			//my page
			$(document).on("click", ".btn_account", function(event) {
				var self = this;
				location.href = "/mypage/main";
			});			

			//상품등록
			$(document).on("click", ".product_save", function(event) {
				var self = this;
				$(".page_form").submit(); return false;
				cmjs_obj.product_add();
			});

			//상품등록취소_중간저장여부
			$(document).on("click", ".product_cancel", function(event) {
				var self = this;
			});
		},
		product_add: function() {
			let self = this;
			let parameter = $(".page_form").serialize();
			$.ajax({
				type: "post",
				data: {parameter},
				async: false,
				url: "/product/product_proc",
				contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
				dataType: "html",
				success: function(res) {
					alert(res);
				}
			});
		},
		run: function() {
			var self = this;
			self.init_load();
		},
	};
	$(function() {
		cmjs_obj.run();
	});
</script>
<!-- -->
</body>

</html>