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
	protected string $prevMessage;

	/**
	 * Output message
	 */
	public function output(
		string $message,
		bool $previousLine = false,
		?ColorEnum $color = null,
	): void
	{
		$output = '';

		if ($previousLine && isset($this->prevMessage)) {
			$output .= chr(27) . "[1A";
			$output .= $this->prevMessage . ' ';
		}

		if ($color) {
			$message = chr(27) . "[" . $color->value . "m" . $message  . chr(27) . "[0m";
		}

		if (!$previousLine) {
			$this->prevMessage = $message;
		}

		$output .= $message;

		echo $output . PHP_EOL;
	}
}
