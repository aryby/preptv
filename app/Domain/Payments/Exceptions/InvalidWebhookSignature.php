<?php

namespace App\Domain\Payments\Exceptions;

use RuntimeException;

class InvalidWebhookSignature extends RuntimeException {}
