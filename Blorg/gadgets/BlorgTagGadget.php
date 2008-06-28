<?php

require_once 'Blorg/BlorgGadget.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'SwatI18N/SwatI18NLocale.php';

/**
 * Displays tags
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgTagGadget extends BlorgGadget
{
	// {{{ protected function displayTitle()

	/**
	 * Displays the title of title of this gadget with a link to the tag page.
	 *
	 * The title is displayed in a h3 element with the CSS class
	 * 'blorg-gadget-title'.
	 */
	protected function displayTitle()
	{
		$header = new SwatHtmlTag('h3');
		$header->class = 'blorg-gadget-title';

		$link = new SwatHtmlTag('a');
		$link->setContent($this->getTitle());
		$link->href = 'tag';

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
		if ($this->getValue('show_empty')) {
			$extra_where = '';
		} else {
			$extra_where = ' and post_count > 0';
		}

		$sql = sprintf('select * from BlorgTag
			inner join BlorgTagVisiblePostCountView
				on BlorgTag.id = BlorgTagVisiblePostCountView.tag
			where instance %s %s %s
			order by title desc',
			SwatDB::equalityOperator($this->app->getInstanceId()),
			$this->app->db->quote($this->app->getInstanceId(), 'integer'),
			$extra_where);

		return SwatDB::query($this->app->db, $sql);
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
