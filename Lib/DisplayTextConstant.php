<?php
/**
 * Lib for setting DisplayText constants throughout the app
 *
 **/
class DisplayTextConstant {
	const CONFIGURE_KEY = 'Layout.DisplayText.constants';

	public static function set($key, $val = null) {
		if (is_array($key)) {
			foreach ($key as $k => $v) {
				self::set($k, $v);
			}
		} else {
			Configure::write(self::CONFIGURE_KEY . '.' . $key, $val);
		}
	}

	public static function check() {
		return Configure::check(self::CONFIGURE_KEY);
	}

	public static function get() {
		if (self::check()) {
			return Configure::read(self::CONFIGURE_KEY);
		} else {
			return array();
		}
	}
}