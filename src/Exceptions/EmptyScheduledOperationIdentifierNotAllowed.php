<?php

/**
 * Common scheduler implementation
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Scheduler\Common\Exceptions;

/**
 *
 */
final class EmptyScheduledOperationIdentifierNotAllowed extends \InvalidArgumentException
{

}
