<?php

/**
 * Displays tags
 *
 * Available settings are:
 *
 * - <kbd>booelan show_empty</kbd>       - whether or not to show empty tags in
 *                                         the list of tags. False by default.
 * - <kbd>booelan show_feed_links</kbd>  - whether or not to show Atom feed
 *                                         links for tags. Defaults to false.
 * - <kbd>boolean show_post_counts</kbd> - whether or not to show a post count
 *                                         for each tag. Defaults to true.
 * - <kbd>boolean cloud_view</kbd>       - whether or not to add CSS hooks
 *                                         for displaying tags as a 'cloud'.
 *                                         Defaults to false.
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgTagGadget extends SiteGadget
{
	// {{{ protected function displayTitle()

	/**
	 * Displays the title of title of this gadget with a link to the tag page.
	 *
	 * The title is displayed in a h3 element with the CSS class
	 * 'site-gadget-title'.
	 */
	protected function displayTitle()
	{
		$header = new SwatHtmlTag('h3');
		$header->class = 'site-gadget-title';

		$link = new SwatHtmlTag('a');
		$link->setContent($this->getTitle());
		$link->href = $this->app->config->blorg->path.'tag';

		$header->open();
		$link->display();
		$header->close();
	}

	// }}}
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		$path = $this->app->config->blorg->path.'tag';

		$tags = $this->getTags();
		if (count($tags) > 0) {
			$ul_tag = new SwatHtmlTag('ul');
			if ($this->getValue('cloud_view')) {
				$ul_tag->class = 'blorg-tag-cloud';
			}
			$ul_tag->open();

			$max = 0;
			foreach ($tags as $tag) {
				if ($tag->post_count > $max) {
					$max = $tag->post_count;
				}
			}

			$locale = SwatI18NLocale::get();
			foreach ($tags as $tag) {
				if ($tag->post_count > 0 || $this->getValue('show_empty')) {
					$popularity = $tag->post_count / $max;

					$li_tag = new SwatHtmlTag('li');
					$li_tag->open();

					$tag_span_tag = new SwatHtmlTag('span');
					$tag_span_tag->class = $this->getTagClass($popularity);
					$tag_span_tag->open();

					$anchor_tag = new SwatHtmlTag('a');
					$anchor_tag->href = $path.'/'.$tag->shortname;
					$anchor_tag->setContent($tag->title);
					$anchor_tag->display();

					if ($this->getValue('show_post_counts')) {
						echo ' ';
						$span_tag = new SwatHtmlTag('span');
						$span_tag->setContent(sprintf(Blorg::ngettext(
							'(%s post)', '(%s posts)', $tag->post_count),
							$locale->formatNumber($tag->post_count)));

						$span_tag->display();
					}

					if ($this->getValue('show_feed_links')) {
						echo ' ';
						$span_tag = new SwatHtmlTag('span');
						$span_tag->class = 'feed';
						$span_tag->open();

						echo '(';

						$anchor_tag = new SwatHtmlTag('a');
						$anchor_tag->class = 'feed';
						$anchor_tag->href = $path.'/'.$tag->shortname.'/feed';
						$anchor_tag->setContent('Feed');
						$anchor_tag->display();

						echo ')';

						$span_tag->close();
					}

					$tag_span_tag->close();

					// breaking space for inline list items in cloud view
					if ($this->getValue('cloud_view')) {
						echo ' ';
					}

					$li_tag->close();
				}
			}

			$ul_tag->close();
		}
	}

	// }}}
	// {{{ protected function define()

	protected function define()
	{
		$this->defineDefaultTitle(Blorg::_('Tags'));
		$this->defineSetting('show_empty', 'Show Empty Tags', 'boolean', false);

		$this->defineSetting('show_feed_links', 'Show Feed Links',
			'boolean', false);

		$this->defineSetting('show_post_counts', 'Show Post Counts',
			'boolean', true);

		$this->defineSetting('cloud_view', 'Use Cloud View',
			'boolean', false);

		$this->defineDescription(Blorg::_(
			'Displays a list of tags and a post count for each tag. This can '.
			'easily be styled to be a “cloud view”.'));

		$this->addStyleSheet(
			'packages/blorg/styles/blorg-tag-gadget.css',
			Blorg::PACKAGE_ID);
	}

	// }}}
	// {{{ protected function getTags()

	protected function getTags()
	{
		$tags = false;

		if (isset($this->app->memcache)) {
			$tags = $this->app->memcache->getNs('tags', 'tags_gadget');
		}

		if ($tags === false) {
			$sql = sprintf('select * from BlorgTag
				inner join BlorgTagVisiblePostCountView
					on BlorgTag.id = BlorgTagVisiblePostCountView.tag
				where instance %s %s
				order by title',
				SwatDB::equalityOperator($this->app->getInstanceId()),
				$this->app->db->quote($this->app->getInstanceId(), 'integer'));

			$tags = SwatDB::query($this->app->db, $sql);

			if (isset($this->app->memcache)) {
				$this->app->memcache->setNs('tags', 'tags_gadget', $tags);
			}
		} else {
			$tags->setDatabase($this->app->db);
		}

		return $tags;
	}

	// }}}
	// {{{ protected function getTagClass()

	protected function getTagClass($popularity)
	{
		if ($popularity > 0.75) {
			$class = 'blorg-tag-popularity-4';
		} elseif ($popularity > 0.5) {
			$class = 'blorg-tag-popularity-3';
		} elseif ($popularity > 0.25) {
			$class = 'blorg-tag-popularity-2';
		} else {
			$class = 'blorg-tag-popularity-1';
		}

		return $class;
	}

	// }}}
}

?>
