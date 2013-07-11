<?php

namespace SitemapPHP;

/**
 * Sitemap
 *
 * This class used for generating Google Sitemap files
 *
 * @package    Sitemap
 * @author     Osman Üngür <osmanungur@gmail.com>
 * @copyright  2009-2011 Osman Üngür
 * @license    http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version    Version @package_version@
 * @since      Class available since Version 1.0.0
 * @link       http://github.com/osmanungur/sitemap-php
 */
class Sitemap {

	/**
	 *
	 * @var \XMLWriter
	 */
	private $writer;
	private $domain;
	private $path;
	private $filename = 'sitemap';
	private $current_item = 0;
	private $current_sitemap = 0;
	private $attributes = array();

	const EXT = '.xml';
	const SCHEMA = 'http://www.sitemaps.org/schemas/sitemap/0.9';
	const DEFAULT_PRIORITY = 0.5;
	const ITEM_PER_SITEMAP = 50000;
	const SEPERATOR = '-';
	const INDEX_SUFFIX = 'index';

	/**
	 *
	 * @param string $domain
	 */
	public function __construct($domain) {
		$this->setDomain($domain);
	}

	/**
	 * Sets root path of the website, starting with http:// or https://
	 *
	 * @param string $domain
	 */
	public function setDomain($domain) {
		$this->domain = $domain;
		return $this;
	}

	/**
	 * Returns root path of the website
	 *
	 * @return string
	 */
	private function getDomain() {
		return $this->domain;
	}

	/**
	 * Returns XMLWriter object instance
	 *
	 * @return \XMLWriter
	 */
	private function getWriter() {
		return $this->writer;
	}

	/**
	 * Assigns XMLWriter object instance
	 *
	 * @param \XMLWriter $writer 
	 */
	private function setWriter(\XMLWriter $writer) {
		$this->writer = $writer;
	}

	/**
	 * Returns path of sitemaps
	 * 
	 * @return string
	 */
	private function getPath() {
		return $this->path;
	}

	/**
	 * Sets paths of sitemaps
	 * 
	 * @param string $path
	 * @return Sitemap
	 */
	public function setPath($path) {
		$this->path = $path;
		return $this;
	}

	/**
	 * Returns filename of sitemap file
	 * 
	 * @return string
	 */
	private function getFilename() {
		return $this->filename;
	}

	/**
	 * Sets filename of sitemap file
	 * 
	 * @param string $filename
	 * @return Sitemap
	 */
	public function setFilename($filename) {
		$this->filename = $filename;
		return $this;
	}
	
	/**
	 * Adds or updates an attribute.
	 * @param string $name
	 * @param string $value
	 * @param Sitemap
	 */
	public function addAttribute($name, $value) {
		$this->attributes[$name] = $value;
		
		return $this;
	}
	
	/**
	 * Function returnes all attributes
	 * @return array
	 */
	public function getAttributes() {
		return $this->attributes;
	}

	/**
	 * Returns current item count
	 *
	 * @return int
	 */
	private function getCurrentItem() {
		return $this->current_item;
	}

	/**
	 * Increases item counter
	 * 
	 */
	private function incCurrentItem() {
		$this->current_item = $this->current_item + 1;
	}

	/**
	 * Returns current sitemap file count
	 *
	 * @return int
	 */
	private function getCurrentSitemap() {
		return $this->current_sitemap;
	}

	/**
	 * Increases sitemap file count
	 * 
	 */
	private function incCurrentSitemap() {
		$this->current_sitemap = $this->current_sitemap + 1;
	}

	/**
	 * Prepares sitemap XML document
	 * 
	 */
	private function startSitemap() {
		$this->setWriter(new \XMLWriter());
		$this->getWriter()->openURI($this->getPath() . $this->getFilename() . self::SEPERATOR . $this->getCurrentSitemap() . self::EXT);
		$this->getWriter()->startDocument('1.0', 'UTF-8');
		$this->getWriter()->setIndent(true);
		$this->getWriter()->startElement('urlset');
		
		$this->addAttribute('xmlns', self::SCHEMA);
		
		foreach ($this->getAttributes() as $name => $value) {
			$this->getWriter()->writeAttribute($name, $value);
		}
	}

	/**
	 * Adds an item to sitemap
	 *
	 * @param string $loc URL of the page. This value must be less than 2,048 characters. 
	 * @param string $priority The priority of this URL relative to other URLs on your site. Valid values range from 0.0 to 1.0.
	 * @param string $changefreq How frequently the page is likely to change. Valid values are always, hourly, daily, weekly, monthly, yearly and never.
	 * @param string|int $lastmod The date of last modification of url. Unix timestamp or any English textual datetime description.
	 * @param array $locales representation of the URL in different locales including the itself, used by Google. E.g. array('de' => http://domain.com/de, 'en' => http://domain.com/en);
	 * @param array $video video data
	 * @return Sitemap
	 * @see http://googlewebmastercentral.blogspot.co.il/2012/05/multilingual-and-multinational-site.html
	 * @see https://support.google.com/webmasters/answer/80472
	 */
	public function addItem($loc, $priority = self::DEFAULT_PRIORITY, $changefreq = NULL, $lastmod = NULL, $locales = array(), $video = array()) {
		if (($this->getCurrentItem() % self::ITEM_PER_SITEMAP) == 0) {
			if ($this->getWriter() instanceof \XMLWriter) {
				$this->endSitemap();
			}
			$this->startSitemap();
			$this->incCurrentSitemap();
		}
		$this->incCurrentItem();
		$this->getWriter()->startElement('url');
		$this->getWriter()->writeElement('loc', $this->getDomain() . $loc);
		if ($lastmod) {
			$this->getWriter()->writeElement('lastmod', $this->getLastModifiedDate($lastmod));
		}
		if ($changefreq) {
			$this->getWriter()->writeElement('changefreq', $changefreq);
		}
		$this->getWriter()->writeElement('priority', $priority);
		
		if ($locales) {
			foreach ($locales as $locale => $path) {
				$this->getWriter()->startElement('xhtml:link');
				$this->getWriter()->writeAttribute('rel', 'alternative');
				$this->getWriter()->writeAttribute('hreflang', $locale);
				$this->getWriter()->writeAttribute('href', $path);
				$this->getWriter()->endElement();
			}
		}
		
		if ($video) {
			$this->getWriter()->startElement('video:video');
			
			foreach ($video as $name => $content) {
				$this->getWriter()->writeElement('video:' . $name, $content);
			}
				
			$this->getWriter()->endElement();
		}
				
		$this->getWriter()->endElement();
		return $this;
	}

	/**
	 * Prepares given date for sitemap
	 *
	 * @param string $date Unix timestamp or any English textual datetime description
	 * @return string Year-Month-Day formatted date.
	 */
	private function getLastModifiedDate($date) {
		if (ctype_digit($date)) {
			return date('Y-m-d', $date);
		} else {
			$date = strtotime($date);
			return date('Y-m-d', $date);
		}
	}

	/**
	 * Finalizes tags of sitemap XML document.
	 *
	 */
	private function endSitemap() {
		if (isset($this->writer)) {
			$this->getWriter()->endElement();
			$this->getWriter()->endDocument();
		}
	}

	/**
	 * Writes Google sitemap index for generated sitemap files
	 *
	 * @param string $loc Accessible URL path of sitemaps
	 * @param string|int $lastmod The date of last modification of sitemap. Unix timestamp or any English textual datetime description.
	 */
	public function createSitemapIndex($loc, $lastmod = 'Today') {
		$this->endSitemap();
		$indexwriter = new \XMLWriter();
		$indexwriter->openURI($this->getPath() . $this->getFilename() . self::SEPERATOR . self::INDEX_SUFFIX . self::EXT);
		$indexwriter->startDocument('1.0', 'UTF-8');
		$indexwriter->setIndent(true);
		$indexwriter->startElement('sitemapindex');
		$indexwriter->writeAttribute('xmlns', self::SCHEMA);
		for ($index = 0; $index < $this->getCurrentSitemap(); $index++) {
			$indexwriter->startElement('sitemap');
			$indexwriter->writeElement('loc', $loc . $this->getFilename() . self::SEPERATOR . $index . self::EXT);
			$indexwriter->writeElement('lastmod', $this->getLastModifiedDate($lastmod));
			$indexwriter->endElement();
		}
		$indexwriter->endElement();
		$indexwriter->endDocument();
	}

}
