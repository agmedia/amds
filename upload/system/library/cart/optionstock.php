<?php
namespace Cart;

class OptionStock {
	public static function isInventorySize(array $option, array $value) {
		// Luceed imports inventory sizes as option 13 with the article UID in sku.
		return (int)$option['option_id'] === 13 && trim((string)($value['sku'] ?? '')) !== '';
	}

	public static function tracksQuantity(array $option, array $value) {
		// subtract controls local decrementing, not whether Luceed stock is available.
		return !empty($value['subtract']) || self::isInventorySize($option, $value);
	}

	public static function cartQuantity(array $rows, $product_id, $product_option_id = null, $value_id = null) {
		$quantity = 0;

		foreach ($rows as $row) {
			if ((int)$row['product_id'] !== (int)$product_id) {
				continue;
			}

			if ($product_option_id !== null) {
				$options = is_string($row['option']) ? json_decode($row['option'], true) : $row['option'];
				$selected = $options[$product_option_id] ?? null;
				$values = is_array($selected) ? $selected : array($selected);
				$matches = false;

				foreach ($values as $value) {
					if (self::isValueId($value) && (int)$value === (int)$value_id) {
						$matches = true;
						break;
					}
				}

				if (!$matches) {
					continue;
				}
			}

			$quantity += max(0, (int)$row['quantity']);
		}

		return $quantity;
	}

	public static function isValueId($value) {
		return (is_string($value) || is_int($value)) && ctype_digit((string)$value) && (int)$value > 0;
	}

	public static function invalidSelections(array $options, array $selection, $quantity, array $cart_rows, $product_id) {
		$errors = array();
		$known = array();

		foreach ($options as $option) {
			$id = $option['product_option_id'];
			$known[$id] = true;
			$selected = $selection[$id] ?? null;

			if ($selected === null || $selected === '' || $selected === array()) {
				if ($option['required']) {
					$errors[$id] = true;
				}
				continue;
			}

			if (!in_array($option['type'], array('select', 'radio', 'checkbox'), true)) {
				if (!is_scalar($selected)) {
					$errors[$id] = true;
				}
				continue;
			}

			if (($option['type'] === 'checkbox') !== is_array($selected)) {
				$errors[$id] = true;
				continue;
			}

			$values = is_array($selected) ? $selected : array($selected);
			$seen = array();

			foreach ($values as $value_id) {
				if (!self::isValueId($value_id) || isset($seen[(int)$value_id])) {
					$errors[$id] = true;
					continue;
				}
				$seen[(int)$value_id] = true;
				$matched = null;

				foreach ($option['product_option_value'] as $value) {
					if ((int)$value['product_option_value_id'] === (int)$value_id) {
						$matched = $value;
						break;
					}
				}

				if ($matched === null) {
					$errors[$id] = true;
					continue;
				}

				$requested = (int)$quantity + self::cartQuantity($cart_rows, $product_id, $id, $value_id);
				if (self::tracksQuantity($option, $matched) && ((int)$quantity < 1 || (float)$matched['quantity'] < $requested)) {
					$errors[$id] = true;
				}
			}
		}

		foreach ($selection as $id => $value) {
			if (!isset($known[$id])) {
				$errors[$id] = true;
			}
		}

		return array_keys($errors);
	}
}
