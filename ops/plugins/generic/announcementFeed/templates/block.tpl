{**
 * plugins/generic/announcementFeed/templates/block.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Announcement feed plugin navigation sidebar.
 *
 *}
<div class="pkp_block block_announcement_feed">
	<span class="title">{translate key="announcement.announcements"}</span>
	<div class="content">
		<ul>
			<li>
				<a href="{url router=$smarty.const.ROUTE_PAGE page="gateway" op="plugin" path="AnnouncementFeedGatewayPlugin"|to_array:"atom"}">
					<img src="{$baseUrl}/lib/pkp/templates/images/atom.svg" alt="{translate key="plugins.generic.announcementfeed.atom.altText"}">
				</a>
			</li>
			<li>
				<a href="{url router=$smarty.const.ROUTE_PAGE page="gateway" op="plugin" path="AnnouncementFeedGatewayPlugin"|to_array:"rss2"}">
					<img src="{$baseUrl}/lib/pkp/templates/images/rss20_logo.svg" alt="{translate key="plugins.generic.announcementfeed.rss2.altText"}">
				</a>
			</li>
			<li>
				<a href="{url router=$smarty.const.ROUTE_PAGE page="gateway" op="plugin" path="AnnouncementFeedGatewayPlugin"|to_array:"rss"}">
					<img src="{$baseUrl}/lib/pkp/templates/images/rss10_logo.svg" alt="{translate key="plugins.generic.announcementfeed.rss1.altText"}">
				</a>
			</li>
		</ul>
	</div>
</div>
