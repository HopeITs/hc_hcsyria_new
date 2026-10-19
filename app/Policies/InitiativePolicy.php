<?php

namespace App\Policies;

/*
 * Initiatives: exactly the rules of ContentPolicy. Legacy did not limit an
 * initiative to a city for the staff who manage it.
 */
class InitiativePolicy extends ContentPolicy {}
