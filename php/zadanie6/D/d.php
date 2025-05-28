<?php

/**
 * Задача D: "Карты, сайты, XML" - создание sitemap в формате XML
 * 
 * Задача: Создать sitemap сайта в формате XML на основе списка разделов.
 * 
 * Формат входных данных:
 * ID;URL;PARENT_ID;TIME
 * - ID - идентификатор раздела: положительное целое число
 * - URL - url раздела: может содержать буквы, цифры и спецсимволы
 * - PARENT_ID - идентификатор родительского раздела: неотрицательное целое число
 * - TIME - время последнего изменения: timestamp
 * 
 * Время последнего изменения раздела зависит от времени изменения его дочерних разделов
 * и должно быть вычислено как максимальное среди времени всех потомков и времени текущего раздела.
 * Разделы в sitemap должны быть отсортированы по ID.
 */

/**
 * Класс для представления раздела сайта
 */
class Section
{
	public int $id;
	public string $url;
	public int $parentId;
	public int $timestamp;
	public array $children = [];
	public int $maxTimestamp;

	/**
	 * Конструктор класса Section
	 * 
	 * @param int $id ID раздела
	 * @param string $url URL раздела
	 * @param int $parentId ID родительского раздела
	 * @param int $timestamp Время последнего изменения
	 */
	public function __construct(int $id, string $url, int $parentId, int $timestamp)
	{
		$this->id = $id;
		$this->url = $url;
		$this->parentId = $parentId;
		$this->timestamp = $timestamp;
		$this->maxTimestamp = $timestamp;
	}

	/**
	 * Преобразование timestamp в формат ISO 8601
	 * 
	 * @return string Время в формате ISO 8601
	 */
	public function getFormattedTime(): string
	{
		return date('c', $this->maxTimestamp);
	}
}

/**
 * Функция для построения дерева разделов
 * 
 * @param array $sections Массив разделов
 * @return array Дерево разделов
 */
function buildSectionTree(array $sections): array
{
	$tree = [];
	$sectionMap = [];

	// Создаем карту разделов по ID
	foreach ($sections as $section) {
		$sectionMap[$section->id] = $section;
	}

	// Строим дерево
	foreach ($sections as $section) {
		if ($section->parentId === 0) {
			// Корневой раздел
			$tree[$section->id] = $section;
		} else if (isset($sectionMap[$section->parentId])) {
			// Добавляем раздел к родителю
			$sectionMap[$section->parentId]->children[] = $section;
		}
	}

	return [$tree, $sectionMap];
}

/**
 * Функция для обновления максимального времени изменения разделов
 * 
 * @param array $sectionMap Карта разделов
 */
function updateMaxTimestamps(array &$sectionMap): void
{
	// Функция для рекурсивного обновления времени
	$updateTime = function ($sectionId) use (&$updateTime, &$sectionMap) {
		$section = $sectionMap[$sectionId];
		$maxTime = $section->timestamp;

		// Проверяем время дочерних разделов
		foreach ($section->children as $child) {
			$childMaxTime = $updateTime($child->id);
			$maxTime = max($maxTime, $childMaxTime);
		}

		$section->maxTimestamp = $maxTime;
		return $maxTime;
	};

	// Обновляем время для всех корневых разделов
	foreach ($sectionMap as $section) {
		if ($section->parentId === 0) {
			$updateTime($section->id);
		}
	}
}

/**
 * Функция для создания sitemap в формате XML
 * 
 * @param array $sections Массив разделов
 * @return string XML-строка sitemap
 */
function generateSitemap(array $sections): string
{
	// Сортируем разделы по ID
	usort($sections, function ($a, $b) {
		return $a->id - $b->id;
	});

	// Формируем XML
	$xml = '<urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9">';

	foreach ($sections as $section) {
		$xml .= '<url>';
		$xml .= '<loc>' . htmlspecialchars($section->url) . '</loc>';
		$xml .= '<lastmod>' . $section->getFormattedTime() . '</lastmod>';
		$xml .= '</url>';
	}

	$xml .= '</urlset>';

	return $xml;
}

// Чтение входных данных
$lines = explode("\n", trim(file_get_contents('php://stdin')));
$sections = [];

foreach ($lines as $line) {
	$line = trim($line);
	if (empty($line)) continue;

	list($id, $url, $parentId, $timestamp) = explode(';', $line);
	$sections[] = new Section((int)$id, $url, (int)$parentId, (int)$timestamp);
}

// Построение дерева разделов
list($tree, $sectionMap) = buildSectionTree($sections);

// Обновление максимального времени изменения
updateMaxTimestamps($sectionMap);

// Генерация sitemap
$sitemap = generateSitemap(array_values($sectionMap));

// Вывод результата
echo $sitemap;
