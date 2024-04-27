<?php

declare(strict_types = 1);

namespace FaimMedia\Migration\Logger;

use FaimMedia\Migration\Logger\{
	ColorEnum,
	LoggerInterface,
};

/**
 * Color output logger
 */
class Color implements LoggerInterface
{
	/**
	 * Output message
	 */
	public function output(
		string $message,
		bool $previousLine = false,
		ColorEnum $color = null,
	): void
	{
		if ($previousLine) {
			echo chr(27) . "[u";
			echo chr(27) . "[A";
			echo " ";
		}

		$output = '';

		if ($color) {
			$message = chr(27) . "[" . $color->value . "m" . $message;
		}

		$output .= $message;

		if (!$previousLine) {
			$output .= chr(27) . "[s";
		}

		$output .= chr(27) . "[0m\n";

		echo $output;
	}
}
