<?php

declare(strict_types = 1);

namespace FaimMedia\Migration\Logger;

use FaimMedia\Migration\Logger\{
	ColorEnum,
	LoggerInterface,
};

/**
 * Noop output logger
 */
class Noop implements LoggerInterface
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
	}
}
