<?php
namespace WPEMS\Templates;

use WPEMS\Model as Md;

interface Pagination {
	public function html_pagination( object $posts = null): string;
}

class WpemsPaginationTemplate implements Pagination {

	public $pagination;
	public function __construct() {
		$this->pagination = Md\WpemPaginationModel::getInstance();
	}
	/**
	 * The Pagination
	 *
	 * @param int $max_num_pages Total number of pages
	 * @param int $pageIndex Current page index
	 * @return string HTML element
	 */
	public function html_pagination( object $posts = null ): string {
		if ( isset( $this->pagination ) && ! empty( $posts ) ) {
			$pag           = $this->pagination->pagination( $posts );
			$max_num_pages = $pag['max_num_pages'];
			$pageIndex     = $pag['pageIndex'];
		}

		$html_template = '<div class="event-pagination"><div class="pagination">%s</div></div>';
		$pagination    = paginate_links(
			array(
				'total'   => $max_num_pages,
				'current' => $pageIndex,
			)
		);
		return sprintf( $html_template, $pagination );
	}
}
