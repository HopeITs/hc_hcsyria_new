<?php

namespace App\Policies;

/*
 * Newsletter items: exactly the rules of ContentPolicy. Legacy did not limit a news
 * item to a city for the staff who manage it.
 */
class NewsPolicy extends ContentPolicy {}
