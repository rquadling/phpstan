<?php declare(strict_types=1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Command\AnalysisResult;
use Symfony\Component\Console\Style\OutputStyle;

class JUnitErrorFormatter implements ErrorFormatter
{

	/**
	 * Formats the errors and outputs them to the console.
	 *
	 * @param \PHPStan\Command\AnalysisResult $analysisResult
	 * @param \Symfony\Component\Console\Style\OutputStyle $style
	 *
	 * @return int Error code.
	 */
	public function formatErrors(
		AnalysisResult $analysisResult,
		OutputStyle $style
	): int {
		$returnCode = 1;
		if (!$analysisResult->hasErrors()) {
			$returnCode = 0;
		}

		$out = '';

		/** @var \PHPStan\Analyser\Error $fileSpecificError */
		foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
			$out .= sprintf(
				'<testcase name="%1$s:%2$s" file="%1$s" assertions="1"><failure type="static_analysis"><![CDATA[%3$s]]></failure></testcase>',
				$this->escape($fileSpecificError->getFile()),
				$this->escape((string)$fileSpecificError->getLine()),
				$this->escape($fileSpecificError->getMessage())
			);
		}

		$style->write('<?xml version="1.0" encoding="UTF-8"?>'."\n");
		$style->write('<testsuites>'."\n");
		if ($out !== '') {
			$style->write($out);
		}
		$style->write('</testsuites>'."\n");

		return $returnCode;
	}

	/**
	 * Escapes values for using in XML
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	protected function escape(string $string): string
	{
		return htmlspecialchars($string, ENT_XML1 | ENT_COMPAT, 'UTF-8');
	}

}

/**
 * <?xml version="1.0" encoding="utf-8"?>
 * <testsuites name="str1234" time="str1234" tests="str1234" failures="str1234" disabled="str1234" errors="str1234">
 * <testsuite name="str1234" tests="str1234" failures="str1234" errors="str1234" time="str1234" disabled="str1234" skipped="str1234" timestamp="str1234" hostname="str1234" id="str1234"
 * package="str1234">
 * <properties>
 * <property name="str1234" value="str1234" />
 * </properties>
 * <testcase name="str1234" assertions="str1234" time="str1234" classname="str1234" status="str1234">
 * <skipped>str1234</skipped>
 * <error type="str1234" message="str1234" />
 * <failure type="str1234" message="str1234" />
 * <system-out>str1234</system-out>
 * <system-err>str1234</system-err>
 * </testcase>
 * <system-out>str1234</system-out>
 * <system-err>str1234</system-err>
 * </testsuite>
 * </testsuites>
 */
