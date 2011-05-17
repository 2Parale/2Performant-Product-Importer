<?php get_header(); ?>
<style type="text/css">
.preview {
	padding:10px 20px;		
}
</style>
<?php
if(isset($_REQUEST['feed_id'])) {
		$feed_id = $_REQUEST['feed_id'];
	}
if(isset($_REQUEST['product_id'])) {
		$product_id = $_REQUEST['product_id'];
	}
if(isset($_REQUEST['template'])) {
		$template = $_REQUEST['template'];
	}
$shortcode = '[tp_product id="' . $product_id . '" feed="' . $feed_id . '" template="' . $template . '"]';
?>
<div class="preview">
<?php echo do_shortcode($shortcode); ?>
</div>
<?php get_footer(); ?>
