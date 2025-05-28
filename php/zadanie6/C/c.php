<?php

/**
 * Задача C: "Фильтр" - преобразование JSON в SQL-запрос
 * 
 * Задача: Преобразовать JSON-структуру в SQL-запрос согласно заданным правилам.
 * 
 * Структура JSON:
 * {
 *   "select": ["field1", "field2"],
 *   "from": "table_name",
 *   "where": {"op_field": "value", "or": {"op_field": "value", "and": {"op_field": "value"}}},
 *   "order": {"field": "how"},
 *   "limit": n
 * }
 * 
 * Правила преобразования описаны в условии задачи.
 */

/**
 * Функция для обработки условий WHERE
 * 
 * @param array $whereConditions Массив условий
 * @return string Строка SQL-условий
 */
function processWhereConditions(array $whereConditions): string
{
	$conditions = [];
	$logicalOperators = ['and' => 'and', 'or' => 'or'];

	foreach ($whereConditions as $key => $value) {
		// Проверяем, является ли ключ логическим оператором (and_* или or_*)
		if (preg_match('/^(and|or)_/', $key, $matches)) {
			$operator = $matches[1];
			// Рекурсивно обрабатываем вложенные условия
			$nestedConditions = processWhereConditions($value);
			$conditions[] = "($nestedConditions)";
			continue;
		}

		// Обработка обычных условий
		$field = $key;
		$operation = '=';

		// Извлекаем операцию из ключа, если она есть
		if (preg_match('/^([<>=!]+)(.+)$/', $key, $matches)) {
			$operation = $matches[1];
			$field = $matches[2];
		}

		// Формируем SQL-условие в зависимости от типа значения и операции
		if ($value === null) {
			$conditions[] = $operation === '!' ? "$field is not null" : "$field is null";
		} elseif (is_bool($value)) {
			$conditions[] = $operation === '!' ? "$field is not $value" : "$field is $value";
		} elseif (is_string($value)) {
			if ($operation === '=') {
				$conditions[] = "$field = '$value'";
			} elseif ($operation === '!') {
				$conditions[] = "$field != '$value'";
			} elseif ($operation === '') {
				$conditions[] = "$field like '$value'";
			} else {
				$conditions[] = "$field $operation '$value'";
			}
		} elseif (is_numeric($value)) {
			if ($operation === '=') {
				$conditions[] = "$field = $value";
			} elseif ($operation === '!') {
				$conditions[] = "$field != $value";
			} else {
				$conditions[] = "$field $operation $value";
			}
		}
	}

	// Соединяем условия с учетом логических операторов
	$result = implode(' and ', $conditions);

	// Обрабатываем вложенные логические операторы
	foreach ($logicalOperators as $key => $operator) {
		if (isset($whereConditions[$key]) && is_array($whereConditions[$key])) {
			$nestedConditions = processWhereConditions($whereConditions[$key]);
			if (!empty($result)) {
				$result .= " $operator ";
			}
			$result .= "($nestedConditions)";
		}
	}

	return $result;
}

/**
 * Функция для преобразования JSON в SQL-запрос
 * 
 * @param string $jsonString JSON-строка
 * @return string SQL-запрос
 */
function jsonToSql(string $jsonString): string
{
	// Декодируем JSON
	$data = json_decode($jsonString, true);

	if (json_last_error() !== JSON_ERROR_NONE) {
		return "Ошибка при разборе JSON: " . json_last_error_msg();
	}

	// Формируем SELECT
	$select = isset($data['select']) && !empty($data['select'])
		? 'select ' . implode(', ', $data['select'])
		: 'select *';

	// Формируем FROM (обязательное поле)
	if (!isset($data['from']) || empty($data['from'])) {
		return "Ошибка: отсутствует обязательное поле 'from'";
	}
	$from = 'from ' . $data['from'];

	// Формируем WHERE
	$where = '';
	if (isset($data['where']) && !empty($data['where'])) {
		$whereConditions = processWhereConditions($data['where']);
		if (!empty($whereConditions)) {
			$where = 'where ' . $whereConditions;
		}
	}

	// Формируем ORDER BY
	$order = '';
	if (isset($data['order']) && !empty($data['order'])) {
		$field = key($data['order']);
		$direction = $data['order'][$field];
		$order = "order by $field $direction";
	}

	// Формируем LIMIT
	$limit = '';
	if (isset($data['limit']) && is_numeric($data['limit'])) {
		$limit = 'limit ' . $data['limit'];
	}

	// Собираем итоговый SQL-запрос
	$sql = $select . PHP_EOL . $from;

	if (!empty($where)) {
		$sql .= PHP_EOL . $where;
	}

	if (!empty($order)) {
		$sql .= PHP_EOL . $order;
	}

	if (!empty($limit)) {
		$sql .= PHP_EOL . $limit;
	}

	return $sql . ';';
}

// Чтение входных данных
$jsonString = file_get_contents('php://stdin');

// Преобразование JSON в SQL
$sqlQuery = jsonToSql($jsonString);

// Вывод результата
echo $sqlQuery . PHP_EOL;
