<?php

namespace App\Policies;

/*
 * The Roots of Hope magazine editions: exactly the rules of ContentPolicy. An edition
 * belongs to no city, and has only the online and offline states (ContentKind::supports()).
 */
class RootsPolicy extends ContentPolicy {}
