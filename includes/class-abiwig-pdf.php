<?php
/**
 * Lightweight PDF generator.
 *
 * @package ABill_Invoice_Generator_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Generate a simple dependency-free invoice PDF.
 *
 * This renderer intentionally uses core PDF Type 1 fonts. It is suitable for
 * the initial release and can be replaced through filters or a future renderer.
 */
final class ABIWIG_PDF {

	/**
	 * Generate PDF bytes.
	 *
	 * @param ABIWIG_Invoice $invoice Invoice object.
	 * @return string|WP_Error
	 */
	public function generate( ABIWIG_Invoice $invoice ) {
		$lines = $this->build_lines( $invoice );
		if ( empty( $lines ) ) {
			return new WP_Error( 'abiwig_pdf_empty', __( 'The invoice does not contain printable data.', 'abill-invoice-generator-for-woocommerce' ) );
		}

		return $this->build_pdf( $lines );
	}

	/**
	 * Write a PDF to a temporary file.
	 *
	 * @param ABIWIG_Invoice $invoice Invoice object.
	 * @return string|WP_Error Temporary filename.
	 */
	public function create_temp_file( ABIWIG_Invoice $invoice ) {
		$pdf = $this->generate( $invoice );
		if ( is_wp_error( $pdf ) ) {
			return $pdf;
		}

		$filename = wp_tempnam( 'abill-invoice-' . sanitize_file_name( $invoice->get_number() ) . '.pdf' );
		if ( ! $filename ) {
			return new WP_Error( 'abiwig_pdf_temp_failed', __( 'Unable to create a temporary PDF file.', 'abill-invoice-generator-for-woocommerce' ) );
		}

		$written = file_put_contents( $filename, $pdf ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Temporary attachment file.
		if ( false === $written ) {
			wp_delete_file( $filename );
			return new WP_Error( 'abiwig_pdf_write_failed', __( 'Unable to write the invoice PDF.', 'abill-invoice-generator-for-woocommerce' ) );
		}

		return $filename;
	}

	/**
	 * Return a safe downloadable filename.
	 *
	 * @param ABIWIG_Invoice $invoice Invoice object.
	 * @return string
	 */
	public function filename( ABIWIG_Invoice $invoice ) {
		return sanitize_file_name( 'abill-invoice-' . $invoice->get_number() . '.pdf' );
	}

	/**
	 * Build printable text lines.
	 *
	 * @param ABIWIG_Invoice $invoice Invoice object.
	 * @return array<int, string>
	 */
	private function build_lines( ABIWIG_Invoice $invoice ) {
		$data     = $invoice->get_data();
		$business = $invoice->get_business();
		$customer = $invoice->get_customer();
		$totals   = $invoice->get_totals();
		$currency = $invoice->get_currency();
		$lines    = array();

		$lines[] = strtoupper( (string) ( $business['name'] ?: __( 'Invoice', 'abill-invoice-generator-for-woocommerce' ) ) );
		$lines[] = __( 'INVOICE', 'abill-invoice-generator-for-woocommerce' );
		$lines[] = '';
		$lines[] = sprintf( '%s: %s', __( 'Invoice number', 'abill-invoice-generator-for-woocommerce' ), $invoice->get_number() );
		$lines[] = sprintf( '%s: %s', __( 'Invoice date', 'abill-invoice-generator-for-woocommerce' ), (string) $data['invoice_date'] );
		if ( ! empty( $data['due_date'] ) ) {
			$lines[] = sprintf( '%s: %s', __( 'Due date', 'abill-invoice-generator-for-woocommerce' ), (string) $data['due_date'] );
		}
		if ( $invoice->get_order_number() ) {
			$lines[] = sprintf( '%s: %s', __( 'Order', 'abill-invoice-generator-for-woocommerce' ), $invoice->get_order_number() );
		}

		$lines[] = '';
		$lines[] = __( 'FROM', 'abill-invoice-generator-for-woocommerce' );
		$lines   = array_merge( $lines, $this->address_lines( $business ) );
		if ( ! empty( $business['tax_id'] ) ) {
			$lines[] = sprintf( '%s: %s', __( 'Tax ID', 'abill-invoice-generator-for-woocommerce' ), $business['tax_id'] );
		}

		$lines[] = '';
		$lines[] = __( 'BILL TO', 'abill-invoice-generator-for-woocommerce' );
		$lines   = array_merge( $lines, $this->address_lines( $customer ) );
		if ( ! empty( $customer['email'] ) ) {
			$lines[] = (string) $customer['email'];
		}

		$lines[] = '';
		$lines[] = str_repeat( '-', 88 );
		$lines[] = sprintf( '%-40s %8s %16s %16s', __( 'Item', 'abill-invoice-generator-for-woocommerce' ), __( 'Qty', 'abill-invoice-generator-for-woocommerce' ), __( 'Tax', 'abill-invoice-generator-for-woocommerce' ), __( 'Total', 'abill-invoice-generator-for-woocommerce' ) );
		$lines[] = str_repeat( '-', 88 );

		foreach ( $invoice->get_items() as $item ) {
			$item_name = (string) $item['name'];
			if ( ! empty( $item['sku'] ) ) {
				$item_name .= ' [' . $item['sku'] . ']';
			}

			$name_parts = $this->wrap( $item_name, 38 );
			$first      = array_shift( $name_parts );
			$lines[]    = sprintf(
				'%-40s %8s %16s %16s',
				$first,
				$this->quantity( $item['quantity'] ),
				$this->money( $item['tax'], $currency ),
				$this->money( $item['total'], $currency )
			);

			foreach ( $name_parts as $part ) {
				$lines[] = (string) $part;
			}
		}

		$lines[] = str_repeat( '-', 88 );
		$lines[] = sprintf( '%72s %16s', __( 'Subtotal:', 'abill-invoice-generator-for-woocommerce' ), $this->money( $totals['subtotal'] ?? 0, $currency ) );
		if ( (float) ( $totals['discount'] ?? 0 ) > 0 ) {
			$lines[] = sprintf( '%72s %16s', __( 'Discount:', 'abill-invoice-generator-for-woocommerce' ), $this->money( $totals['discount'], $currency ) );
		}
		if ( (float) ( $totals['shipping'] ?? 0 ) > 0 ) {
			$lines[] = sprintf( '%72s %16s', __( 'Shipping:', 'abill-invoice-generator-for-woocommerce' ), $this->money( $totals['shipping'], $currency ) );
		}
		if ( (float) ( $totals['tax'] ?? 0 ) > 0 ) {
			$lines[] = sprintf( '%72s %16s', __( 'Tax:', 'abill-invoice-generator-for-woocommerce' ), $this->money( $totals['tax'], $currency ) );
		}
		$lines[] = sprintf( '%72s %16s', __( 'TOTAL:', 'abill-invoice-generator-for-woocommerce' ), $this->money( $totals['total'] ?? 0, $currency ) );

		if ( ! empty( $data['notes'] ) ) {
			$lines[] = '';
			$lines[] = __( 'NOTES', 'abill-invoice-generator-for-woocommerce' );
			$lines   = array_merge( $lines, $this->wrap( (string) $data['notes'], 88 ) );
		}

		if ( ! empty( $data['terms'] ) ) {
			$lines[] = '';
			$lines[] = __( 'TERMS', 'abill-invoice-generator-for-woocommerce' );
			$lines   = array_merge( $lines, $this->wrap( (string) $data['terms'], 88 ) );
		}

		/**
		 * Filter text lines before PDF construction.
		 *
		 * @param array<int, string> $lines   PDF lines.
		 * @param ABIWIG_Invoice    $invoice Invoice object.
		 */
		return (array) apply_filters( 'abiwig_pdf_lines', $lines, $invoice );
	}

	/**
	 * Build address lines.
	 *
	 * @param array<string, mixed> $address Address data.
	 * @return array<int, string>
	 */
	private function address_lines( $address ) {
		$lines = array();
		foreach ( array( 'name', 'company', 'address_1', 'address_2' ) as $key ) {
			if ( ! empty( $address[ $key ] ) ) {
				$lines[] = (string) $address[ $key ];
			}
		}

		$city_line = trim( implode( ' ', array_filter( array( $address['city'] ?? '', $address['state'] ?? '', $address['postcode'] ?? '' ) ) ) );
		if ( $city_line ) {
			$lines[] = $city_line;
		}
		if ( ! empty( $address['country'] ) ) {
			$lines[] = (string) $address['country'];
		}

		return $lines;
	}

	/** @return string */
	private function money( $amount, $currency ) {
		$decimals = function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 2;
		return trim( (string) $currency . ' ' . number_format_i18n( (float) $amount, $decimals ) );
	}

	/** @return string */
	private function quantity( $quantity ) {
		$quantity = (float) $quantity;
		return floor( $quantity ) === $quantity ? (string) (int) $quantity : rtrim( rtrim( number_format( $quantity, 3, '.', '' ), '0' ), '.' );
	}

	/**
	 * Wrap text while preserving paragraph breaks.
	 *
	 * @param string $text  Text.
	 * @param int    $width Width.
	 * @return array<int, string>
	 */
	private function wrap( $text, $width ) {
		$lines = array();
		foreach ( preg_split( '/\R/u', $text ) ?: array() as $paragraph ) {
			$wrapped = wordwrap( trim( $paragraph ), $width, "\n", true );
			foreach ( explode( "\n", $wrapped ) as $line ) {
				$lines[] = $line;
			}
		}
		return $lines;
	}

	/**
	 * Build a valid multi-page PDF document.
	 *
	 * @param array<int, string> $lines Text lines.
	 * @return string
	 */
	private function build_pdf( $lines ) {
		$paper      = (string) abiwig_get_setting( 'pdf_paper_size', 'A4' );
		$page_width = 'LETTER' === $paper ? 612 : 595;
		$page_height= 'LETTER' === $paper ? 792 : 842;
		$line_height= 13;
		$top        = $page_height - 46;
		$bottom     = 48;
		$per_page   = max( 1, (int) floor( ( $top - $bottom ) / $line_height ) );
		$pages      = array_chunk( $lines, $per_page );
		$page_count = count( $pages );
		$font_obj   = 3 + ( $page_count * 2 );
		$objects    = array();

		$objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
		$kids       = array();
		foreach ( $pages as $index => $page_lines ) {
			$page_obj    = 3 + ( $index * 2 );
			$content_obj = 4 + ( $index * 2 );
			$kids[]      = $page_obj . ' 0 R';
			$stream      = '';
			$y           = $top;

			foreach ( $page_lines as $line_index => $line ) {
				$size   = 9;
				$font   = '/F1';
				$x      = 42;
				$source = rtrim( (string) $line );

				if ( 0 === $line_index && 0 === $index ) {
					$size = 15;
				} elseif ( __( 'INVOICE', 'abill-invoice-generator-for-woocommerce' ) === $source ) {
					$size = 18;
				}

				$stream .= sprintf( "BT %s %d Tf %d %d Td (%s) Tj ET\n", $font, $size, $x, $y, $this->pdf_escape( $source ) );
				$y      -= $line_height;
			}

			$objects[ $content_obj ] = "<< /Length " . strlen( $stream ) . ">>\nstream\n" . $stream . "endstream";
			$objects[ $page_obj ]    = sprintf(
				'<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %d %d] /Resources << /Font << /F1 %d 0 R >> >> /Contents %d 0 R >>',
				$page_width,
				$page_height,
				$font_obj,
				$content_obj
			);
		}

		$objects[2]         = '<< /Type /Pages /Kids [' . implode( ' ', $kids ) . '] /Count ' . $page_count . ' >>';
		$objects[ $font_obj ] = '<< /Type /Font /Subtype /Type1 /BaseFont /Courier /Encoding /WinAnsiEncoding >>';
		ksort( $objects );

		$pdf     = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
		$offsets = array( 0 );
		foreach ( $objects as $number => $object ) {
			$offsets[ $number ] = strlen( $pdf );
			$pdf .= $number . " 0 obj\n" . $object . "\nendobj\n";
		}

		$xref = strlen( $pdf );
		$pdf .= "xref\n0 " . ( count( $objects ) + 1 ) . "\n";
		$pdf .= "0000000000 65535 f \n";
		for ( $i = 1; $i <= count( $objects ); $i++ ) {
			$pdf .= sprintf( "%010d 00000 n \n", $offsets[ $i ] );
		}
		$pdf .= "trailer\n<< /Size " . ( count( $objects ) + 1 ) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";

		return $pdf;
	}

	/**
	 * Convert UTF-8 text to the PDF's WinAnsi encoding and escape syntax.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	private function pdf_escape( $text ) {
		if ( function_exists( 'iconv' ) ) {
			$encoded = iconv( 'UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text );
			if ( false !== $encoded ) {
				$text = $encoded;
			}
		} else {
			$text = preg_replace( '/[^\x20-\x7E]/', '?', $text );
		}

		return str_replace( array( '\\', '(', ')', "\r", "\n" ), array( '\\\\', '\\(', '\\)', '', ' ' ), (string) $text );
	}
}
