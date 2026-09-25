<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Exception;

/** HTTP 400 — некорректный/отсутствующий параметр запроса (`400000`/`400002`/`400007`/`400008`/`400009`). */
class ValidationException extends KonturOfdException {}
