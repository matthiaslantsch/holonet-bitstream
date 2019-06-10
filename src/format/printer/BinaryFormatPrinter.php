<?php
/**
 * This file is part of the bitstream package
 * (c) Matthias Lantsch
 *
 * class file for the BinaryFormatPrinter class
 *
 * @package holonet bitstream library
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\bitstream\format\printer;

use holonet\common as co;
use holonet\bitstream\format\BinNode;
use holonet\bitstream\format\BinStruct;
use holonet\bitstream\format\BinInteger;
use holonet\bitstream\format\BinSkip;
use holonet\bitstream\format\BinTranslate;
use holonet\bitstream\format\BinArray;
use holonet\bitstream\format\BinCallback;
use holonet\bitstream\format\BinOptional;
use holonet\bitstream\format\BinBlob;
use holonet\bitstream\format\BinChoice;
use holonet\bitstream\format\BinBoolean;
use holonet\bitstream\format\InvalidFormatException;

/**
 * BinaryFormatPrinter is used to generate php code out of a binary file tree
 *
 * @author  matthias.lantsch
 * @package holonet\bitstream\format\printer
 */
class BinaryFormatPrinter {

	/**
	 * property containing the tree that we want to print
	 *
	 * @access protected
	 * @var    BinNode $tree The BinNode to be printed
	 */
	protected $tree;

	/**
	 * constructor method for the BinaryFormatPrinter definition object
	 *
	 * @access public
	 * @param  BinNode $tree The BinNode to be printed
	 * @return void
	 */
	public function __construct(BinNode $tree) {
		$this->tree = $tree;
	}

	/**
	 * method used to actually generate the php code representing the tree
	 *
	 * @access public
	 * @return string with the generated php code
	 */
	public function print() {
		return $this->generateLine($this->tree);
	}

	/**
	 * small helper method genereting a string out of one node
	 *
	 * @access private
	 * @param  BinNode $node The node to be printed out
	 * @param  int $indent The number of tabs to be used when generating code
	 * @return string with the generated php code
	 */
	private function generateLine($node, int $indent = 0) {
		if($node === null) {
			return "null";
		} elseif(is_string($node)) {
			return "\"{$node}\"";
		} elseif(is_array($node)) {
			return co\indentText($this->generateDefinition($node), $indent);
		}
		$class = co\get_class_name(get_class($node));
		if($class === "BinBlob") {
			$params = "{$this->generateDefinition($node->size)}";
		} elseif($class === "BinArray") {
			$params = "\n\t{$this->generateDefinition($node->size)},\n\t{$this->generateLine($node->tree, 1)}\n";
		} elseif($class === "BinCallback") {
			$params = "{$this->closureDump($node->callback)}";
		} elseif($class === "BinChoice") {
			$params = "{$this->generateLine($node->tree)}, [\n";
			foreach ($node->map as $key => $value) {
				$params .= "\t{$key} => {$this->generateLine($value, 1)},\n";
			}
			$params .= "]";
		} elseif($class === "BinInteger") {
			if($node->size === 0) {
				return $node->constant;
			}
			$params = $this->generateDefinition($node->size);
			if($node->constant != 0) {
				$params .= ", {$node->constant}";
			}
			if($node->endian != "big_endian") {
				$params .= ", {$node->endian}";
			}
		} elseif($class === "BinOptional") {
			$params = "{$this->generateLine($node->tree)}, {$this->generateDefinition($node->condition)}";
		} elseif($class === "BinSkip") {
			$params = "{$this->generateDefinition($node->size)}, ".($node->alignBeforeParsing ? "true" : "false");
		} elseif($class === "BinStruct" || $class === "BinSerialisedData") {
			$params = "[\n";
			foreach ($node->tree as $key => $subnode) {
				$params .= sprintf("\t%s%s,\n", (is_string($key) ? "\"{$key}\" => " : ""), $this->generateLine($subnode, 1));
			}
			$params .= "]";
			if($node->class !== null) {
				$params .= ",\n\"{$node->class}\"\n";
			}
		} elseif($class === "BinTranslate") {
			$params = "{$this->generateLine($node->tree)}, {$this->generateDefinition($node->translation)}";
		} elseif($class === "BinBoolean") {
			$params = "";
		} elseif($class === "BinDelta") {
			$params = $this->generateLine($node->tree);
		}else {
			throw new InvalidFormatException("don't know how to print binary node ".var_export($node, true), 150);
		}

		return co\indentText(sprintf('new %s(%s)',
			$class,
			$params
		), $indent);
	}

	/**
	 * small helper method genereting a string out of a php definition
	 *
	 * @access private
	 * @param  mixed $def Value definition
	 * @return string with the generated php code
	 */
	private function generateDefinition($def) {
		if($def instanceof BinNode) {
			return $this->generateLine($def);
		} elseif(is_int($def)) {
			return $def;
		} elseif(is_string($def)) {
			return "\"{$def}\"";
		} elseif(is_array($def)) {
			$ret = "[";
			foreach ($def as $key => $value) {
				$ret .= "\n\t{$this->generateDefinition($key)} => ".co\indentText($this->generateDefinition($value), 1).",";
			}
			return rtrim($ret, ",")."\n]";
		} elseif(is_callable($def)) {
			return $this->closureDump($def);
		} else {
			throw new InvalidFormatException("Unknown size definition ".var_export($def, true), 100);
		}
	}

	/**
	 * method using reflection to get the source code of a closure
	 * https://stackoverflow.com/questions/25586109/view-a-php-closures-source
	 *
	 * @access private
	 * @param  Closure $c The closure to get the source code for
	 * @return string with the generated php code
	 */
	private function closureDump(Closure $c) {
		$str = 'function (';
		$r = new ReflectionFunction($c);
		$params = array();
		foreach($r->getParameters() as $p) {
			$s = '';
			if($p->isArray()) {
				$s .= 'array ';
			} else if($p->getClass()) {
				$s .= $p->getClass()->name . ' ';
			}
			if($p->isPassedByReference()){
				$s .= '&';
			}
			$s .= '$' . $p->name;
			if($p->isOptional()) {
				$s .= ' = ' . var_export($p->getDefaultValue(), TRUE);
			}
			$params []= $s;
		}
		$str .= implode(', ', $params);
		$str .= '){' . PHP_EOL;
		$lines = file($r->getFileName());
		for($l = $r->getStartLine(); $l < $r->getEndLine(); $l++) {
			$str .= $lines[$l];
		}
		return $str;
	}

}
