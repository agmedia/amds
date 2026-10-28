<?php
class ModelExtensionBaselNewsletter extends Model {
	public function checkExist($data) {
	   $query=$this->db->query("SELECT * FROM " . DB_PREFIX . "newsletter WHERE LOWER(email)='" . $this->db->escape(strtolower(trim($data['email']))) . "'");
	   return $query->num_rows;
	}
	
	public function checkRegistered($data) {
	   $query=$this->db->query("SELECT * FROM " . DB_PREFIX . "customer WHERE LOWER(email)='" . $this->db->escape(strtolower(trim($data['email']))) . "'");
	   return $query->num_rows;
	}
	
	public function UpdateRegistered($data,$status) {
		$email = strtolower(trim($data['email']));

		if (!$this->syncKlaviyoNewsletter($email, (bool)$status)) {
			return false;
		}

		$this->db->query("UPDATE " . DB_PREFIX . "customer SET newsletter ='" . (int)$status . "' WHERE LOWER(email)='" . $this->db->escape($email) . "'");

		return true;
	}
		
	public function subscribe($data) {
		$email = strtolower(trim($data['email']));

		if (!$this->syncKlaviyoNewsletter($email, true)) {
			return false;
		}

		if (!$this->checkExist(array('email' => $email))) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "newsletter SET email='" . $this->db->escape($email) . "'");
		}

		return true;
	}
	
	public function unsubscribe($data) {
		$email = strtolower(trim($data['email']));

		if (!$this->syncKlaviyoNewsletter($email, false)) {
			return false;
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "newsletter WHERE LOWER(email)='" . $this->db->escape($email) . "'");

		return true;
	}

	private function syncKlaviyoNewsletter($email, $subscribed) {
		try {
			$this->load->library('klaviyo');
			$result = $this->klaviyo->queueNewsletterConsent($email, $subscribed);

			if (!$result['success']) {
				$this->log->write('[klaviyo] basel newsletter queue failed: email_hash=' . hash('sha256', $email) . ' subscribed=' . ($subscribed ? '1' : '0') . ' error=' . $result['error']);
				return false;
			}

			return true;
		} catch (\Throwable $e) {
			$this->log->write('[klaviyo] basel newsletter queue exception: email_hash=' . hash('sha256', $email));
			return false;
		}
	}
   
}
