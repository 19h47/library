<?php
/**
 * The families of achievements.
 *
 * @link       https://github.com/19h47/library
 * @since      9.25.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

namespace Library;

/**
 * A family of achievements, as the achievements page groups them, in its order.
 *
 * @since      9.25.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
enum Achievement_Family: string {

	case Completions = 'completions';
	case Milestones  = 'milestones';
	case Discoveries = 'discoveries';
	case Forms       = 'forms';
	case Languages   = 'languages';
	case Easter_Eggs = 'easter-eggs';

	/**
	 * Its name, as the achievements page titles it.
	 *
	 * @return string
	 */
	public function label(): string {
		return match ( $this ) {
			self::Completions => __( 'Completions', 'library' ),
			self::Milestones  => __( 'Milestones', 'library' ),
			self::Discoveries => __( 'Discoveries', 'library' ),
			self::Forms       => __( 'Forms', 'library' ),
			self::Languages   => __( 'Languages and translators', 'library' ),
			self::Easter_Eggs => __( 'Easter eggs', 'library' ),
		};
	}
}
