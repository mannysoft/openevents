<?php

namespace App\Services;

use RuntimeException;

/**
 * The Lobby answered, but not with what the caller needs: for example a holding page, or a
 * sign-in with no access token. Guzzle's own exceptions cover a Lobby that does not answer
 * at all or answers with an error status.
 */
class LobbyUnavailableException extends RuntimeException
{
}
