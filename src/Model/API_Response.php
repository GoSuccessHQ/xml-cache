<?php
/**
 * Simple API response DTO for consistent REST output.
 *
 * @package xml-cache
 */

declare( strict_types=1 );

namespace GoSuccess\XML_Cache\Model;

defined( 'ABSPATH' ) || exit;

/**
 * Class Api_Response
 *
 * Represents a response from the API.
 */
final class API_Response {
	/**
	 * Constructor.
	 *
	 * @param bool        $success Success flag.
	 * @param array|null  $data    Payload data.
	 * @param string|null $message Message text.
	 */
	public function __construct(
		private bool $success = false,
		private ?array $data = null,
		private ?string $message = null
	) {}

	/**
	 * Gets the success status of the API response.
	 *
	 * @return bool True if the API call was successful, false otherwise.
	 */
	public function is_success(): bool {
		return $this->success;
	}

	/**
	 * Sets the success status of the API response.
	 *
	 * @param bool $success The success status.
	 * @return self
	 */
	public function set_success( bool $success ): self {
		$this->success = $success;
		return $this;
	}

	/**
	 * Gets the data returned by the API.
	 *
	 * @return array|null The data returned by the API, or null if not set.
	 */
	public function get_data(): ?array {
		return $this->data;
	}

	/**
	 * Sets the data for the API response.
	 *
	 * @param array|null $data The data to set.
	 * @return self
	 */
	public function set_data( ?array $data ): self {
		$this->data = $data;
		return $this;
	}

	/**
	 * Gets the message describing the API response.
	 *
	 * @return string|null The message, or null if not set.
	 */
	public function get_message(): ?string {
		return $this->message;
	}

	/**
	 * Sets the message for the API response.
	 *
	 * @param string|null $message The message to set.
	 * @return self
	 */
	public function set_message( ?string $message ): self {
		$this->message = $message;
		return $this;
	}

	/**
	 * Convert response to array for rest_ensure_response.
	 *
	 * @return array<string,mixed> Response data.
	 */
	public function to_array(): array {
		return array(
			'success' => $this->success,
			'data'    => $this->data,
			'message' => $this->message,
		);
	}
}
