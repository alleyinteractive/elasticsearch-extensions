<?php
/**
 * Elasticsearch Extensions: Custom_Date_Range Aggregation Class
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Aggregations;

use DateTime;
use Elasticsearch_Extensions\DSL;
use Exception;

/**
 * Custom date range aggregation class. Responsible for building the DSL and
 * requests for aggregations as well as holding the result of the aggregation
 * after a response was received.
 */
class Custom_Date_Range extends Aggregation {

	/**
	 * Configure the Custom Date Range aggregation.
	 *
	 * @param DSL   $dsl  The DSL object, initialized with the map from the adapter.
	 * @param array $args Optional. Additional arguments to pass to the aggregation.
	 */
	public function __construct( DSL $dsl, array $args ) {
		$this->label     = __( 'Custom Date Range', 'elasticsearch-extensions' );
		$this->query_var = 'custom_date_range';

		parent::__construct( $dsl, $args );
	}

	/**
	 * Overrides the extract_query_values function in the primary Aggregation
	 * class to allow for additional processing on date query values.
	 *
	 * @param string $key Optional. The key to look up. Defaults to the current query var.
	 *
	 * @return string[] The values for the given key.
	 */
	protected function extract_query_values( string $key = '' ): array {
		$query_var = $key ?: $this->get_query_var();

		/*
		 * Get the raw query values and replace whitespace with + characters.
		 * When ISO-8601 dates are added to URLs, timezone offsets are added via
		 * a + followed by the hours and minutes, which when URL-decoded results
		 * in a space rather than a +. Rather than depending on the front-end to
		 * always properly encode the +, we provide a fallback here to interpret
		 * a space as a plus for the purposes of date encoding.
		 */
		$query_values = array_map(
			function( $query_value ) {
				return str_replace( ' ', '+', $query_value );
			},
			(array) ( $_GET['fs'][ $query_var ] ?? [] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
		);

		// This filter is documented in class-aggregation.php.
		return apply_filters(
			'elasticsearch_extensions_aggregation_query_values',
			array_values( array_filter( $query_values ) ),
			$this
		);
	}

	/**
	 * Gets an array of DSL representing each filter for this aggregation that
	 * should be applied in the query in order to match the requested values.
	 *
	 * @return array Array of DSL fragments to apply.
	 */
	public function filter(): array {
		$date_range = $this->get_date_range(
			$this->query_values[0] ?? '',
			$this->query_values[1] ?? '',
		);

		return ! empty( $date_range )
			? [ $this->dsl->range( 'post_date', $date_range ) ]
			: [];
	}

	/**
	 * Given a start and end date in ISO-8601 format, constructs a from/to date
	 * range suitable for use in Elasticsearch DSL.
	 *
	 * @param string $from The start date, in ISO-8601 format.
	 * @param string $to   The end date, in ISO-8601 format.
	 *
	 * @return array An array containing timestamps for from and to.
	 */
	private function get_date_range( string $from, string $to ) : array {
		try {
			$from_datetime = DateTime::createFromFormat( DATE_W3C, $from );
			$to_datetime   = DateTime::createFromFormat( DATE_W3C, $to );

			if ( empty( $from_datetime ) && empty( $to_datetime ) ) {
				return [];
			}

			return $this->dsl->build_range( $from_datetime, $to_datetime );
		} catch ( Exception $e ) {
			return [];
		}
	}

	/**
	 * Overrides the default input function for aggregations to print a set of
	 * two date input fields that allow users to set a start and end date for
	 * this aggregation.
	 */
	public function input(): void {
		$timezone = wp_timezone();
		$fields = [
			[
				'date_w3c' => '',
				'date_ymd' => '',
				'endtime'  => 'T00:00:00+00:00',
				'label'    => __( 'Start Date', 'elasticsearch-extensions' ),
			],
			[
				'date_w3c' => '',
				'date_ymd' => '',
				'endtime'  => 'T23:59:59+00:00',
				'label'    => __( 'End Date', 'elasticsearch-extensions' ),
			],
		];
		try {
			foreach ( $fields as $index => &$config ) {
				$datetime = DateTime::createFromFormat( DATE_W3C, $this->get_query_values()[ $index ] ?? '', $timezone );
				if ( $datetime ) {
					$config['date_w3c'] = $datetime->format( DATE_W3C );
					$config['date_ymd'] = $datetime->format( 'Y-m-d' );
				}
			}
		} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Fail silently, since we already established default values above.
		}
		?>
		<fieldset class="elasticsearch-extensions__custom-date-range-group">
			<legend><?php echo esc_html( $this->get_label() ); ?></legend>
			<?php foreach ( $fields as $field ) : ?>
				<label>
					<?php echo esc_html( $field['label'] ); ?>
					<input
						name="fs[<?php echo esc_attr( $this->query_var ); ?>][]"
						type="hidden"
						value="<?php echo esc_attr( $field['date_w3c'] ); ?>"
					/>
					<input
						onchange='this.previousElementSibling.value = this.value ? this.value + <?php echo wp_json_encode( $field['endtime'] ); ?> : ""'
						type="date"
						value="<?php echo esc_attr( $field['date_ymd'] ); ?>"
					/>
				</label>
			<?php endforeach; ?>
		</fieldset>
		<?php
	}

	/**
	 * Since there are no aggregation parameters sent with the request, we do
	 * not need to parse the buckets on the response.
	 *
	 * @param array $buckets The raw aggregation buckets from Elasticsearch.
	 */
	public function parse_buckets( array $buckets ): void {}

	/**
	 * This aggregation works a bit differently than the others, since it's more
	 * of a filter based on user-supplied values, so we don't need to add any
	 * aggregation parameters to the request.
	 *
	 * @return array DSL fragment.
	 */
	public function request(): array {
		return [];
	}
}
