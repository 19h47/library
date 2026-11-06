<?php
/**
 * The kinds of achievements.
 *
 * @link       https://github.com/19h47/library
 * @since      9.25.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

namespace Library;

/**
 * A kind of achievement, in the order of the achievements page, with its family and its dashicon. Its value
 * names it in the achievements the REST API gives.
 *
 * @since      9.25.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
enum Achievement_Kind: string {

	case Author              = 'author';
	case Series              = 'series';
	case Collection          = 'collection';
	case Collection_Step     = 'collection-step';
	case Read                = 'read';
	case Pages               = 'pages';
	case Record_Year         = 'record-year';
	case Record_Month        = 'record-month';
	case Streak              = 'streak';
	case Reread              = 'reread';
	case Hot_Off_The_Press   = 'hot-off-the-press';
	case Centenarian         = 'centenarian';
	case Archaeologist       = 'archaeologist';
	case Decades             = 'decades';
	case Form                = 'form';
	case Jack_Of_All_Trades  = 'jack-of-all-trades';
	case Library_Tour        = 'library-tour';
	case Languages           = 'languages';
	case Original_Language   = 'original-language';
	case Polyglot            = 'polyglot';
	case Rosetta_Stone       = 'rosetta-stone';
	case Translator          = 'translator';
	case Go_Between          = 'go-between';
	case Tsundoku            = 'tsundoku';
	case Leap_Day            = 'leap-day';
	case Pi_Day              = 'pi-day';
	case New_Year            = 'new-year';
	case New_Years_Eve       = 'new-years-eve';
	case Friday_13th         = 'friday-13th';
	case Palindrome          = 'palindrome';
	case Love_At_First_Sight = 'love-at-first-sight';
	case Brick               = 'brick';
	case One_That_Got_Away   = 'one-that-got-away';
	case Mouthful            = 'mouthful';
	case Page_404            = 'page-404';
	case Fahrenheit_451      = 'fahrenheit-451';
	case Ultimate_Question   = 'ultimate-question';
	case Splits              = 'splits';
	case Everest             = 'everest';
	case Time_Machine        = 'time-machine';
	case Twins               = 'twins';
	case Marathon            = 'marathon';
	case Seven_Days_A_Week   = 'seven-days-a-week';
	case Hat_Trick           = 'hat-trick';
	case Namesakes           = 'namesakes';
	case Back_To_The_Start   = 'back-to-the-start';

	/**
	 * Its family.
	 *
	 * @return Achievement_Family
	 */
	public function family(): Achievement_Family {
		return match ( $this ) {
			self::Author,
			self::Series,
			self::Collection,
			self::Collection_Step => Achievement_Family::Completions,
			self::Read,
			self::Pages,
			self::Record_Year,
			self::Record_Month,
			self::Streak,
			self::Reread => Achievement_Family::Milestones,
			self::Hot_Off_The_Press,
			self::Centenarian,
			self::Archaeologist,
			self::Decades => Achievement_Family::Discoveries,
			self::Form,
			self::Jack_Of_All_Trades,
			self::Library_Tour => Achievement_Family::Forms,
			self::Languages,
			self::Original_Language,
			self::Polyglot,
			self::Rosetta_Stone,
			self::Translator,
			self::Go_Between => Achievement_Family::Languages,
			self::Tsundoku,
			self::Leap_Day,
			self::Pi_Day,
			self::New_Year,
			self::New_Years_Eve,
			self::Friday_13th,
			self::Palindrome,
			self::Love_At_First_Sight,
			self::Brick,
			self::One_That_Got_Away,
			self::Mouthful,
			self::Page_404,
			self::Fahrenheit_451,
			self::Ultimate_Question,
			self::Splits,
			self::Everest,
			self::Time_Machine,
			self::Twins,
			self::Marathon,
			self::Seven_Days_A_Week,
			self::Hat_Trick,
			self::Namesakes,
			self::Back_To_The_Start => Achievement_Family::Easter_Eggs,
		};
	}

	/**
	 * Its dashicon, without its prefix: "awards".
	 *
	 * @return string
	 */
	public function icon(): string {
		return match ( $this ) {
			self::Author              => 'admin-users',
			self::Series              => 'book',
			self::Collection          => 'category',
			self::Collection_Step     => 'chart-pie',
			self::Read                => 'awards',
			self::Pages               => 'media-document',
			self::Record_Year         => 'chart-bar',
			self::Record_Month        => 'chart-line',
			self::Streak              => 'calendar-alt',
			self::Reread              => 'image-rotate',
			self::Hot_Off_The_Press   => 'clock',
			self::Centenarian         => 'backup',
			self::Archaeologist       => 'search',
			self::Decades             => 'admin-site-alt3',
			self::Form                => 'tag',
			self::Jack_Of_All_Trades  => 'screenoptions',
			self::Library_Tour        => 'location-alt',
			self::Languages           => 'admin-site',
			self::Original_Language   => 'format-chat',
			self::Polyglot            => 'admin-site-alt',
			self::Rosetta_Stone       => 'admin-site-alt2',
			self::Translator          => 'translation',
			self::Go_Between          => 'randomize',
			self::Tsundoku            => 'book-alt',
			self::Leap_Day            => 'calendar',
			self::Pi_Day              => 'calculator',
			self::New_Year            => 'flag',
			self::New_Years_Eve       => 'beer',
			self::Friday_13th         => 'warning',
			self::Palindrome          => 'image-flip-horizontal',
			self::Love_At_First_Sight => 'heart',
			self::Brick               => 'database',
			self::One_That_Got_Away   => 'migrate',
			self::Mouthful            => 'editor-quote',
			self::Page_404            => 'no-alt',
			self::Fahrenheit_451      => 'lightbulb',
			self::Ultimate_Question   => 'editor-help',
			self::Splits              => 'leftright',
			self::Everest             => 'chart-area',
			self::Time_Machine        => 'update',
			self::Twins               => 'controls-repeat',
			self::Marathon            => 'performance',
			self::Seven_Days_A_Week   => 'schedule',
			self::Hat_Trick           => 'star-filled',
			self::Namesakes           => 'groups',
			self::Back_To_The_Start   => 'undo',
		};
	}
}
