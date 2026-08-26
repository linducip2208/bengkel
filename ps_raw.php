<?php

passthru('php vendor/phpstan/phpstan/phpstan analyse --no-progress --memory-limit=1G --error-format=raw 2>&1', $code);
