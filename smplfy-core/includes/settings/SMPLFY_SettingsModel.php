<?php
namespace SmplfyCore;
class SMPLFY_SettingsModel {
	private string $apiUrl;
	private string $apiKey;
	private bool $sendToDataDog;
	private bool $sendNotices;
	private bool $sendDeprecated;

	function __construct( string $apiKey, string $apiUrl, bool $sendToDataDog, bool $sendNotices, bool $sendDeprecated ) {
		$this->sendToDataDog = $sendToDataDog;
		$this->sendNotices = $sendNotices;
		$this->sendDeprecated = $sendDeprecated;
		$this->apiKey        = $apiKey;
		$this->apiUrl        = $apiUrl;
	}

	public function get_api_key(): string {
		return $this->apiKey;
	}

	public function is_send_to_data_dog(): bool {
		return $this->sendToDataDog;
	}
	public function is_send_notices(): bool {
		return $this->sendNotices;

	}
	public function is_send_deprecated(): bool {
		return $this->sendDeprecated;
	}
	public function get_api_url(): string {
		return $this->apiUrl;
	}
}