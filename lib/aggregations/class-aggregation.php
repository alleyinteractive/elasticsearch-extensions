<?php
/**
 * Elasticsearch Extensions: Aggregation Abstract Class
 *
 * @package Elasticsearch_Extensions
 */

namespace Elasticsearch_Extensions\Aggregations;

use Elasticsearch_Extensions\DSL;

/**
 * Aggregation abstract class. Responsible for building the DSL and requests
 * for aggregations as well as holding the result of the aggregation after a
 * response was received.
 */
abstract class Aggregation {

	/**
	 * Results for this aggregation from Elasticsearch. An array of Bucket objects.
	 *
	 * @var Bucket[]
	 */
	protected array $buckets = [];

	/**
	 * A reference to the DSL class, initialized with the map from the adapter.
	 *
	 * @var DSL
	 */
	protected DSL $dsl;

	/**
	 * The human-readable label for this aggregation.
	 *
	 * @var string
	 */
	protected string $label = '';

	/**
	 * The order to apply to the results. One of 'ASC', 'DESC'.
	 * Defaults to 'DESC'.
	 *
	 * @var string
	 */
	protected $order = 'DESC';

	/**
	 * The field to sort results by. Defaults to 'count'. Can also be 'key' or
	 * 'label' or a field specific to an implementing class.
	 *
	 * @var string
	 */
	protected $orderby = 'count';

	/**
	 * The query var this aggregation should use.
	 *
	 * @var string
	 */
	protected string $query_var = '';

	/**
	 * The values for the query var for this aggregation.
	 *
	 * @var string[]
	 */
	protected array $query_values = [];

	/**
	 * Build the aggregation type object.
	 *
	 * @param DSL   $dsl  The DSL object, initialized with the map from the adapter.
	 * @param array $args Optional. Additional arguments to pass to the aggregation.
	 */
	public function __construct( DSL $dsl, array $args = [] ) {
		$this->dsl = $dsl;
		foreach ( $args as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->$key = $value;
			}
		}

		// Extract selected values from the query var.
		$this->query_values = $this->extract_query_values();
	}

	/**
	 * Outputs checkboxes for all buckets in the aggregation.
	 */
	public function checkboxes() {
		// Bail if we have no buckets.
		if ( empty( $this->buckets ) ) {
			return;
		}

		?>
		<fieldset class="elasticsearch-extensions__checkbox-group">
			<legend><?php echo esc_html( $this->get_label() ); ?></legend>
			<?php foreach ( $this->buckets as $bucket ) : ?>
				<label>
					<input
						<?php checked( $bucket->selected ); ?>
						name="fs[<?php echo esc_attr( $this->query_var ); ?>][]"
						type="checkbox"
						value="<?php echo esc_attr( $bucket->key ); ?>"
					/>
					<?php echo esc_html( $bucket->label ); ?> (<?php echo esc_html( $bucket->count ); ?>)
				</label>
			<?php endforeach; ?>
		</fieldset>
		<?php
	}

	/**
	 * A helper function for getting query values for the current query var or
	 * for an arbitrary query var. We can't use get_query_var() here because
	 * custom query var registration happens too late for our purposes, so we
	 * need to do it manually.
	 *
	 * @param string $key Optional. The key to look up. Defaults to the current query var.
	 *
	 * @return string[] The values for the given key.
	 */
	protected function extract_query_values( string $key = '' ): array {
		return array_values( array_filter( (array) ( $_GET['fs'][ $key ?: $this->get_query_var() ] ?? [] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Get DSL for filters that should be applied in the DSL in order to match
	 * the requested values.
	 *
	 * @return array|null DSL fragment or null if no filters to apply.
	 */
	abstract public function filter(): ?array;

	/**
	 * Gets a list of results for this aggregation.
	 *
	 * @return Bucket[] An array of Bucket objects.
	 */
	public function get_buckets(): array {
		return $this->buckets;
	}

	/**
	 * Gets the human-readable label for this aggregation.
	 *
	 * @return string The human-readable label for this aggregation.
	 */
	public function get_label(): string {
		return $this->label;
	}

	/**
	 * Get the query var for this aggregation.
	 *
	 * @return string The query var for this aggregation.
	 */
	public function get_query_var(): string {
		return $this->query_var;
	}

	/**
	 * Get the values for the query var for this aggregation.
	 *
	 * @return array The values for the query var.
	 */
	public function get_query_values(): array {
		return $this->query_values;
	}

	/**
	 * Determines whether the specified key is selected in the query for this
	 * aggregation.
	 *
	 * @param string $key The key to check.
	 *
	 * @return bool True if selected, false if not.
	 */
	protected function is_selected( string $key ): bool {
		return in_array( $key, $this->query_values, true );
	}

	/**
	 * Given a raw array of Elasticsearch aggregation buckets, parses it into
	 * Bucket objects and passes them to save_buckets for finalization.
	 *
	 * @param array $buckets The raw aggregation buckets from Elasticsearch.
	 */
	abstract public function parse_buckets( array $buckets ): void;

	/**
	 * Get DSL for the aggregation to add to the Elasticsearch request object.
	 * Instructs Elasticsearch to return buckets for this aggregation in the
	 * response.
	 *
	 * @return array DSL fragment.
	 */
	abstract public function request(): array;

	/**
	 * Outputs a select control for all buckets in the aggregation.
	 */
	public function select() {
		// Bail if we have no buckets.
		if ( empty( $this->buckets ) ) {
			return;
		}

		?>
		<div class="elasticsearch-extensions__select-control">
			<label for="<?php echo esc_attr( $this->get_query_var() ); ?>">
				<?php echo esc_html( $this->get_label() ); ?>
			</label>
			<select
				id="<?php echo esc_attr( $this->get_query_var() ); ?>"
				name="fs[<?php echo esc_attr( $this->query_var ); ?>][]"
			>
				<option value="">
					<?php esc_html_e( 'All', 'elasticsearch-extensions' ); ?>
				</option>
				<?php foreach ( $this->buckets as $bucket ) : ?>
					<option
						<?php selected( $bucket->selected ); ?>
						value="<?php echo esc_attr( $bucket->key ); ?>"
					>
						<?php echo esc_html( $bucket->label ); ?> (<?php echo esc_html( $bucket->count ); ?>)
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	/**
	 * Performs post-processing on buckets before saving them to the object.
	 *
	 * @param Bucket[] $buckets The buckets to save.
	 */
	protected function set_buckets( array $buckets ): void {
		/**
		 * Allows the buckets to be filtered before they are displayed, which
		 * can allow for removing certain items, or changing labels, or changing
		 * the sort order of buckets.
		 *
		 * @param Bucket[]    $buckets     The array of buckets to filter.
		 * @param Aggregation $aggregation The aggregation that the buckets are associated with.
		 */
		$this->buckets = apply_filters( 'elasticsearch_extensions_buckets', $this->sort_buckets( $buckets ), $this );
	}

	/**
	 * Apply default sorting rules based on count, key, and label. Can be
	 * overridden by implementing classes to allow for custom sort logic.
	 *
	 * @param Bucket[] $buckets Buckets to be sorted.
	 *
	 * @return Bucket[] The sorted bucket array.
	 */
	protected function sort_buckets( array $buckets ): array {

		// If the sort is one of the standard keys, apply it.
		if ( in_array( $this->orderby, [ 'count', 'key', 'label' ], true ) ) {
			usort(
				$buckets,
				/**
				 * Compares two buckets to determine which should come first.
				 *
				 * @param Bucket $a The first bucket to compare.
				 * @param Bucket $b The second bucket to compare.
				 *
				 * @return int Less than one if a is before b, more than one if b is before a, or zero if they are equal.
				 */
				function ( Bucket $a, Bucket $b ): int {
					switch ( $this->orderby ) {
						case 'key':
							return strcasecmp( $a->key, $b->key );
						case 'label':
							return strcasecmp( $a->label, $b->label );
						default:
							return $a->count - $b->count;
					}
				}
			);
		}

		// If the sort order is descending, flip the order.
		if ( 'DESC' === $this->order ) {
			$buckets = array_reverse( $buckets );
		}

		return $buckets;
	}
}
