<!-- CONTENT -->
<section class="container">
	<div class="row">
		<div class="col-4 left_main">
			<h1>Random Pick</h1>
			<h2>무조건 당첨 !</h2>
			<a href="/home/login">logins </a>
		</div>
		<div class="col-7">
			<header>
				<form class="page_search_form" onsubmit="return false">
					<div class="input-group col-8">
						<input type="text" class="form-control" name="top_input_serach" id="top_input_serach">
						<div class="input-group-append">
							<span class="input-group-text"><img class="btn_top_search" src="/assets/search.svg"></span>
						</div>
					</div>


					<div class="input-group head-nav">
						<div class="col-12 float-left">
							<!-- <label class="my_location_label">My location: </label><span class="my_location"> Novena</span> -->

							<!-- <div class="col-4 float-right">
								<label class="my_distance_label">Distance: </label>
								<select name="search_location" id="top_select_location">
									<option value="250">250m</option>
									<option value="500">500m</option>
									<option value="1000">1km</option>
									<option value="2000">2km</option>
									<option value="5000">5km</option>
									<option value="10000">10km</option>									
								</select>
							</div> -->
						</div>
					</div>
				</form>

			</header>
			<!-- <div class="card" style="width: 18rem;">
				<img src="..." class="card-img-top" alt="...">
				<div class="card-body">
					<h5 class="card-title">Card title</h5>
					<p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
					<a href="#" class="btn btn-primary">Go somewhere</a>
				</div>
			</div> -->
			<article class="product_list">

				<ul class="list-unstyled">

				</ul>
			</article>
			<footer style="display:block;  clear: both;">
				<div class="row">
					<div class="col-3"></div>
					<div class="col-3"><img class="btn_home" src="/assets/home.svg">
						<p>home</p>
					</div>
					<div class="col-3"><img class="btn_chat" src="/assets/message-square.svg">
						<p>chat</p>
					</div>
					<div class="col-3"><img class="btn_account" src="/assets/user.svg">
						<p>Account</p>
					</div>
				</div>
				<img class="pluscircle float-right" src="/assets/plus-circle.svg">
			</footer>
		</div>
		<div class="col-1 right_main"></div>
	</div>
</section>

<!-- SCRIPTS -->
<script>
	'use strict';
	var main_product_obj = {
		init_load: function() {
			let self = this;
			self.ajax_product();

			$(document).on("click", ".btn_top_search", function(event) {
				var self = this;
				main_product_obj.ajax_product();
			});
			$(document).on("click", ".link_product", function(event) {
				var self = this;
				let id = $(this).data("id");
				location.href = "product/product_detail?product_id=" + id;
			});
			$(document).on("click", ".go_pick", function(event) {
				var self = this;
				main_product_obj.ajax_pick();

			});

		},
		ajax_product: function() {
			let self = this;
			let parameter = $(".page_search_form").serialize();
			$.ajax({
				type: "get",
				data: {
					parameter
				},
				async: false,
				url: "/product/product_list",
				contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
				dataType: "json",
				success: function(res) {
					//change must
					if (res.code == 200) {
						$(".product_list").html("");
						$.each(res.data, function(k, v) {

							let html = '<li class="media my-4">  \
							<img class="link_product rounded float-left img-thumbnail p-img" src="' + v["img_url"] + '" data-id=' + v["product_id"] + ' alt="..." /> \
							<div class="media-body"><div calss="float-left"><h5 class="mt-0 mb-1">' + v["product_name"] + '</h5></div> \
								<div><span> 쥬얼리 의원</span> </div> \
								<div><span> ' + v["price"] + '원</span> </div> \
								<div><span class="float-left">별점 8.5</span> <button class="float-right go_pick">응모</button</div> \
							</div></li>';
							$(".product_list").append(html);
						});
					}
				}
			});
		},
		ajax_product_paging: function() {
			let self = this;
			let parameter = $(".page_search_form").serialize();
			$.ajax({
				type: "post",
				data: {
					parameter
				},
				async: false,
				url: "/product/product_list",
				contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
				dataType: "json",
				success: function(res) {
					//console.log(res.code);
					//change must
					if (res.code == 200) {
						$.each(res.data, function(k, v) {

							let html = '<li class="media my-4">  \
							<img class="rounded float-left img-thumbnail p-img" src="' + v["product_thumbnail"] + '" alt="..." /> \
							<div class="media-body"><div calss="float-left"><h6 class="mt-0 mb-1">' + v["product_name"] + '</h6></div> \
								<div><span>$ ' + v["price"] + '</span> SGD</div> \
								<div><span class="float-left">10 mins a go</span> <span class="float-right">위시</span><span class="float-right">활성챗</span></div> \
							</div></li>';
							$(".product_list").append(html);
						});
					}
				}
			});
		},
		ajax_pick: function() {
			let self = this;
			let parameter = $(".page_search_form").serialize();
			$.ajax({
				type: "get",
				data: {
					parameter
				},
				async: false,
				url: "/product/go_pick",
				contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
				dataType: "json",
				success: function(res) {
					//change must
					if (res.code == 200) {						
						alert(res.message);
					}
				}
			});
		},
		run: function() {
			var self = this;
			self.init_load();
		},
	};

	$(function() {
		main_product_obj.run();
	});
</script>


<!-- <li class="media my-4">
						<img class="rounded float-left img-thumbnail p-img" src="/uploads/1650258796_bdae1b68c4495cc11766.jpg" alt="..." />
						<div class="media-body">
						<div calss="float-left"><h5 class="mt-0 mb-1">ipthone</h5></div>
							<div><span>$ 500</span> SGD</div>
							<div><span class="float-left">10 mins a go</span> <span class="float-right">위시</span><span class="float-right">활성챗</span></div>
						</div>
					</li> -->

<!-- <nav class="navbar navbar-expand-lg navbar-light bg-white">
	<a class="navbar-brand" href="#">Navbar</a>
	<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
		<span class="navbar-toggler-icon"></span>
	</button>

	<div class="collapse navbar-collapse" id="navbarSupportedContent">
		<ul class="navbar-nav mr-auto">
		</ul>
		<form class="form-inline my-2 my-lg-0">
			<div class="nav-item dropdown">
				<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-expanded="false">
					Dropdown
				</a>
				<div class="dropdown-menu" aria-labelledby="navbarDropdown">
					<a class="dropdown-item" href="#">Action</a>
					<a class="dropdown-item" href="#">Another action</a>
					<div class="dropdown-divider"></div>
					<a class="dropdown-item" href="#">Something else here</a>
				</div>
		</form>
	</div>
</nav> -->