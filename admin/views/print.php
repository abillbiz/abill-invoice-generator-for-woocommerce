<?php
/**
 * Standalone printable invoice shell.
 *
 * Expected variable: $abiwig_invoice.
 *
 * @package ABill_Invoice_Generator_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>
		<?php
		/* translators: %s: invoice number. */
		echo esc_html( sprintf( __( 'Invoice %s', 'abill-invoice-generator-for-woocommerce' ), $abiwig_invoice->get_number() ) );
		?>
	</title>
	<?php wp_print_styles( 'abiwig-print' ); ?>
</head>
<body class="abiwig-print-page">
	<?php
	abiwig_get_template(
		'invoice-default.php',
		array(
			'invoice'     => $abiwig_invoice,
			'context'     => 'print',
			'show_status' => false,
		)
	);
	?>
	<?php wp_print_scripts( 'abiwig-print' ); ?>
</body>
</html>
