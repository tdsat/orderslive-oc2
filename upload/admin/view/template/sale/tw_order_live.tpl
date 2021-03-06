<?= $header ?>
	<?= $column_left ?>
		<div id="content">
			<nav class="navbar navbar-default">
				<div class="container-fluid">
					<div class="navbar-header">
						<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#tw-settings">
						<span class="sr-only">Toggle navigation</span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						</button>
						<div class="navbar-brand">
							<span id="tw-toggle-live"></span>LIVE! <span id="tw-live-version"> <?= "v".TW_ORDERS_LIVE_VERSION ?></span>|
							<span id="server-info"><?= $text_connection ?> <span id="server-status" class="label label-default"><?= $status_unknown ?></span> </span>
							<small id="tw-response-time" data-toggle="tooltip" title="<?= $text_average_response_time?>"></small>
						</div>
					</div>
					<div class="collapse navbar-collapse" id="tw-settings">
						<div class="row">
							<ul class="nav navbar-nav" id="order-count">
								<li><?= $text_orders ?></li>
								<li>
									<span id="count-new" data-toggle="tooltip" title="<?= $filter_new ?>" class="label label-primary">0 </span>
								</li>
								<li>
									<span id="count-complete" data-toggle="tooltip" title="<?= $filter_complete ?>" class="label label-success">0 </span>
								</li>
								<li>
									<span id="count-pending" data-toggle="tooltip" title="<?= $filter_pending ?>" class="label label-warning">0</span>
								</li>
								<li>
									<span id="count-misc" data-toggle="tooltip" title="<?= $filter_misc ?>" class="label label-default">0</span>
								</li>
							</ul>
							<ul class="nav navbar-nav navbar-right">
								<li>
									<form class="form-inline">
										<div class="checkbox">
											<label data-toggle="tooltip" title="<?= $text_continuous_info ?>"><input v-model="continuous_sound" type="checkbox"> <?= $text_continuous ?></label>
										</div>
										<div class="checkbox">
											<label data-toggle="tooltip" title="<?= $text_mute_info ?>"><input v-model="mute_sound" type="checkbox"> <?= $text_mute ?></label>
										</div>
										<div class="form-group input-group input-group-sm">
											<select class="form-control" v-model="sound_file" data-toggle="tooltip" title="<?= $text_sound_info ?>">
												<?php foreach ($sound_files as $sound_file) {
													echo '<option value="'.$sound_file['file'].'">' . $sound_file['name'].'</option>';
												} ?>
											</select>
											<span class="input-group-btn">
												<button class="btn btn-default" id="sound-stop" data-toggle="tooltip" type="button"><i class="fa fa-pause fa-lg"></i></button>
											</span>
											<span class="input-group-btn">
												<button class="btn btn-default" id="sound-preview" data-toggle="tooltip" title="<?= $text_play_sound ?>" type="button"><i class="fa fa-play fa-lg"></i></button>
											</span>
										</div>
									</form>
								</li>
							</ul>
						</div>
						<div class="row collapse" id="order-filters">
							<div class="col-sm-12 navbar-form">
								<button id="tw-reset-filters" type="button" class="btn btn-xs" data-toggle="tooltip" title="<?= $help_reset_filters ?>" ><span>&times;</span></button>												
								<div class="btn-group btn-group-xs" data-toggle="buttons">
									<label class="btn btn-default">
										<input type="radio" v-model="filter_key" name="filter_key" value="" autocomplete="off"><?= $filter_all ?>
									</label>
									<label class="btn btn-success">
										<input type="radio" v-model="filter_key" name="filter_key" value="complete" autocomplete="off"><?= $filter_complete ?>
									</label>
									<label class="btn btn-warning">
										<input type="radio" v-model="filter_key" name="filter_key" value="pending" autocomplete="off"><?= $filter_pending ?>
									</label>
									<label class="btn btn-misc">
										<input type="radio" v-model="filter_key" name="filter_key" value="misc" autocomplete="off"><?= $filter_misc ?>
									</label>
								</div>
								<div class="btn-group btn-group-xs" data-toggle="buttons">
									<label class="btn btn-default" data-toggle="tooltip" title="<?= $help_date_arrived ?>">
										<input type="radio" v-model="sort_key" name="sort_key" value="arrived" autocomplete="off"><?= $sort_date_arrived ?>
									</label>
									<label class="btn btn-default" data-toggle="tooltip" title="<?= $help_date_modified ?>">
										<input type="radio" v-model="sort_key" name="sort_key" value="timestamp" autocomplete="off"><?= $sort_date_modified ?>
									</label>
									<label class="btn btn-default">
										<input type="radio" v-model="sort_key" name="sort_key" value="status-id" autocomplete="off"><?= $text_status ?>
									</label>
									<label class="btn btn-default">
										<input type="radio" v-model="sort_key" name="sort_key" value="order-id" autocomplete="off" checked><?= $sort_order_id ?>
									</label>
									<label class="btn btn-default">
										<input type="radio" v-model="sort_key" name="sort_key" value="order-group" autocomplete="off"><?= $sort_status_group ?>
									</label>
								</div>
								<div class="btn-group btn-group-xs" data-toggle="buttons">
									<label class="btn btn-default">
										<input type="radio" v-model="sort_direction" name="sort_direction" value="descending" autocomplete="off"><?= $sort_desc ?>
									</label>
									<label class="btn btn-default">
										<input type="radio" v-model="sort_direction" name="sort_direction" value="ascending" autocomplete="off"><?= $sort_asc ?>
									</label>
								</div>
								
								<div class="checkbox">
									<label data-toggle="tooltip" title="<?= $help_always_show_new ?>">
										<input type="checkbox" v-model="always_show_new">
										<?= $text_always_show_new ?>
									</label>
								</div>
								<div class="checkbox">
									<label data-toggle="tooltip" title="<?= $help_new_always_on_top ?>">
										<input type="checkbox" v-model="new_always_on_top">
										<?= $text_new_always_on_top ?>
									</label>
								</div>
							</div>
						</div>
					</div>
				</div>
			</nav>
			<button class="btn btn-primary button-sm" data-toggle="collapse" data-target="#order-filters" id="filter-toggle">
				<?= $text_filters ?>
			</button>


			<div class="container-fluid">
				<div class="row">
					<div class="col-sm-3 col-md-2" style="overflow:auto;height:70rem;">
						<ul id="order-tabs" class="nav nav-pills nav-stacked">
							<?php
								foreach($order_tabs as $tab) echo $tab;
							?>
						</ul>
						
						<p class="text-center" ><a href="#" id="tw-load-more"><?= $text_load_more_orders ?></a></p>
						
					</div>
					<div class="col-sm-9 col-md-10">
						<div id="order-details" class="tab-content">
							<?php
								foreach($order_details as $details) echo $details;
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
<script src="view/javascript/tw/moment-with-locales.min.js" type="text/javascript"></script>
<script src="view/javascript/tw/js.cookie.min.js" type="text/javascript"></script>
<script src="view/javascript/tw/web-animations.min.js" type="text/javascript"></script>
<script src="view/javascript/tw/muuri.min.js" type="text/javascript"></script>
<script src="view/javascript/tw/watch.min.js" type="text/javascript"></script>
<link rel="stylesheet" href="view/stylesheet/tw/tw_order_live.css">

<script>
//Server Status
const ServerStatuses = Object.freeze({
	UNKNOWN : 0,
	OK      : 1,
	ERROR   : 2,
	STOPPED : 3,
	title : {
		0 : '<?= $status_unknown ?>',
		1 : '<?= $status_ok ?>',
		2 : '<?= $status_error ?>',
		3 : '<?= $status_stopped ?>'
	},
	style :{
		0 : "label-default",
		1 : "label-success",
		2 : "label-danger",
		3 : "label-warning"
	}
});
moment.locale("<?= $locale ?>");

//Extract all the needed php variables uses but the scripts
var token = '<?= $token ?>';
var last_order_id = window.last_order_id ? window.last_order_id : 0;
var tw_live_timestamp = window.tw_live_timestamp ? window.tw_live_timestamp : 0;
var tw_order_page = 1;
var api_token = '';
var catalog = '<?= $catalog ?>';
var api_ip = '<?= $api_ip ?>';
var api_id = '<?= $api_id?>';
var api_key = '<?= $api_key ?>';

//Array that hold the ids of the hiden/deleted orders. Used when undoing
var order_data_undo_array = new Array();
</script>
<script src="view/javascript/tw/orderslive.js" type="text/javascript"></script>
<?=  $footer; ?>