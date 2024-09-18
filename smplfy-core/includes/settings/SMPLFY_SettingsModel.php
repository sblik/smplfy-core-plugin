<?php
namespace SmplfyCore;
class SMPLFY_SettingsModel {
	private string $apiUrl;
	private string $apiKey;
	private bool $sendToDataDog;

	function __construct( string $apiKey, string $apiUrl, bool $sendToDataDog ) {
		$this->sendToDataDog = $sendToDataDog;
		$this->apiKey        = $apiKey;
		$this->apiUrl        = $apiUrl;
	}

	public function get_api_key(): string {
		return $this->apiKey;
	}

	public function is_send_to_data_dog(): bool {
		return $this->sendToDataDog;
	}

	public function get_api_url(): string {
		return $this->apiUrl;
	}
}