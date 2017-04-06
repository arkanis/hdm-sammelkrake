<?php

require_once(dirname(__FILE__) . '/../include/view_helpers.php');

/**
 * Recursive DOM iterator taken from https://github.com/salathe/spl-examples/wiki/RecursiveDOMIterator
 */
class RecursiveDOMIterator implements RecursiveIterator
{
    /**
     * Current Position in DOMNodeList
     * @var Integer
     */
    protected $_position;

    /**
     * The DOMNodeList with all children to iterate over
     * @var DOMNodeList
     */
    protected $_nodeList;

    /**
     * @param DOMNode $domNode
     * @return void
     */
    public function __construct(DOMNode $domNode)
    {
        $this->_position = 0;
        $this->_nodeList = $domNode->childNodes;
    }

    /**
     * Returns the current DOMNode
     * @return DOMNode
     */
    public function current()
    {
        return $this->_nodeList->item($this->_position);
    }

    /**
     * Returns an iterator for the current iterator entry
     * @return RecursiveDOMIterator
     */
    public function getChildren()
    {
        return new self($this->current());
    }

    /**
     * Returns if an iterator can be created for the current entry.
     * @return Boolean
     */
    public function hasChildren()
    {
        return $this->current()->hasChildNodes();
    }

    /**
     * Returns the current position
     * @return Integer
     */
    public function key()
    {
        return $this->_position;
    }

    /**
     * Moves the current position to the next element.
     * @return void
     */
    public function next()
    {
        $this->_position++;
    }

    /**
     * Rewind the Iterator to the first element
     * @return void
     */
    public function rewind()
    {
        $this->_position = 0;
    }

    /**
     * Checks if current position is valid
     * @return Boolean
     */
    public function valid()
    {
        return $this->_position < $this->_nodeList->length;
    }
}


// The SBar page contains stange HTML. Therefore load it with the DOM
// parser that can handle broken HTML.
$dom = new DOMDocument();
@$dom->loadHTMLFile('http://www.s-bar.de/eat-n-talk-hdm/speiseplan.html');
$xpath = new DOMXPath($dom);

/*
// Right now there is no menu. Just a placeholder text. Fetch that text
// and strip non blocking spaces around it (they inserted them to provoke
// line breaks…).
$sbar_text = $xpath->evaluate('string(//div[@class="content"])');
// A Unicode aware trim. Required to get rid of stupid non-breaking spaces. Removes
// any leading and trailing characters with the separator and "other" (control, format, etc.)
// property. The docs state that this is slow, but well.
// See http://de.php.net/manual/en/regexp.reference.unicode.php
$sbar_text = preg_replace('/^[\pZ\pC]*|[\pZ\pC]*$/u', '', $sbar_text);
*/

$week_plan = array();
$para = $xpath->query('//div[@class="content"]/div')->item(0);
$para->normalize();
$para_iterator = new RecursiveIteratorIterator( new RecursiveDOMIterator($para), RecursiveIteratorIterator::SELF_FIRST );
$empty_text_nodes = 0;

foreach($para_iterator as $node){
	if ($node->nodeType == XML_ELEMENT_NODE and $node->tagName == 'strong') {
		//echo("elem " . $node->tagName . ": " . $node->nodeValue . "\n");
		$week_plan[trim($node->nodeValue)] = array();
		$empty_text_nodes = 0;
	} elseif ($node->nodeType == XML_TEXT_NODE) {
		$latest_day = end(array_keys($week_plan));
		// Skip stuff if we don't have a heading yet
		if ($latest_day === false)
			continue;
		
		// A Unicode aware trim. Required to get rid of stupid non-breaking spaces. Removes
		// any leading and trailing characters with the separator and "other" (control, format, etc.)
		// property. The docs state that this is slow, but well.
		// See http://de.php.net/manual/en/regexp.reference.unicode.php
		$text = preg_replace('/^[\pZ\pC]*|[\pZ\pC]*$/u', '', $node->nodeValue);
		if ($text == '')
			$empty_text_nodes++;
		//printf("text: '%s', %d\n", $text, $empty_text_nodes);
		// Stop processing if 3 or more empty text nodes come in a row. Hope is that 3 empty text nodes will
		// always be there before the PDF link at the bottom.
		if ($empty_text_nodes > 2)
			break;
		
		// Skip empty text nodes or the text node of the headings
		if ($text == '' or $text == $latest_day)
			continue;
		
		$week_plan[$latest_day][] = $text;
	}
}

// Capture the output we want to write into the tile
ob_start();

?>
<article id="sbar" class="misc changing" data-width="2" data-height="1">
	<h2><a href="http://www.s-bar.de/eat-n-talk-hdm/speiseplan.html">S-Bar Wochenkarte</a></h2>
	
	<ul>
<?php	foreach($week_plan as $day => $dishes): ?>
		<li>
			<span title="<?= ha($day) ?>"><?= h($day) ?></span>
			<ul>
<?php			foreach($dishes as $dish): ?>
				<li title="<?= ha($dish) ?>"><?= h($dish) ?></li>
<?php			endforeach ?>
			</ul>
		</li>
<?php	endforeach ?>
	</ul>
</article>
<?php

// And store the captured output into the tile
file_put_contents(dirname(__FILE__) . '/../tiles/12-sbar.php', ob_get_clean());

?>
