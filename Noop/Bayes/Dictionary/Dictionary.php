<?php

/**
 * MIT licensed
 */

namespace Noop\Bayes\Dictionary;

use Noop\Bayes\Token\TokenArray;

class Dictionary implements \Serializable
{
    /**
     * Dictionary of all tokens
     * @var array
     */
    protected $dictionary;

    /**
     * Current document count
     * @var integer
     */
    protected $documentCount;

    /**
     * Minimal frequency in document for token to be included.
     * For example, if this is 0.1, then all tokens found in less than 1 doc of
     * 10 will be not taken in account
     * @var double
     */
    protected $minimalFrequencyInDocuments;

    /**
     * Whether to use $minimalFrequencyInDocuments
     * @var boolean
     */
    protected $useDocumentCount;

    /**
     * Minimal token length to be processed
     * @var integer
     */
    protected $minimalTokenLength;

    /**
     * Maximal token length to be processed
     * @var integer
     */
    protected $maximalTokenLength;

    /**
     * Dictionary size
     * @var integer
     */
    protected $tokenCount;

    /**
     * Usable token count (not filtered by length, stemmer or so on)
     * @var integer
     */
    protected $usableTokenCount;

    public function __construct()
    {
        $this->dictionary = array();
        $this->tokenCount = 0;
        $this->documentCount = 0;
        $this->minimalFrequencyInDocuments = 0.05;
        $this->useDocumentCount(false);
        $this->minimalTokenLength = 3;
        $this->maximalTokenLength = 16;
    }

    /**
     * Adds tokens to dictionary
     * @param TokenArray $tokens
     */
    public function addTokens(TokenArray $tokens)
    {
        foreach ($tokens as $token => $count) {
            if (isset($this->dictionary[$token])) {
                $this->dictionary[$token]['count'] += $count;
            } else {
                $this->dictionary[$token]['count'] = $count;
            }
        }

        $this->documentCount ++;

        $this->recount();
    }

    /**
     * Removes tokens to dictionary
     * @param TokenArray $tokens
     */
    public function removeTokens(TokenArray $tokens)
    {
        foreach ($tokens as $token => $count) {
            if (isset($this->dictionary[$token])) {
                $this->dictionary[$token]['count'] -= $count;

                if ($this->dictionary[$token]['count'] <= 0) {
                    unset($this->dictionary[$token]);
                }
            }
        }

        $this->documentCount--;

        $this->recount();
    }

    /**
     * summates tokens and recounts probabilities
     */
    protected function recount()
    {
        // count dictionary size
        $this->tokenCount = array_reduce($this->dictionary,
                function($previous, $value) {
                    return $value['count'] + $previous;
                }, 0);

        $this->usableTokenCount = 0;

        // check tokens
        foreach (array_keys($this->dictionary) as $token) {

            // skip tokens that are less popular than $minimalFrequencyInDocs, of applicable
            if($this->useDocumentCount() && $this->getDocumentCount() > 0) {
                if($data['count'] / $this->documentCount < $this->getMinimalFrequencyInDocuments()) {
                    $this->dictionary[$token]['weight'] = 0;
                    continue;
                }
            }

            // max/min token length
            if(mb_strlen($token) > $this->getMaximalTokenLength() ||
                    mb_strlen($token) < $this->getMinimalTokenLength()) {
                $this->dictionary[$token]['weight'] = 0;
                continue;
            }

            // this is temporary value before normalization
            $this->dictionary[$token]['weight'] = 1;
            $this->usableTokenCount += $this->dictionary[$token]['count'];
        }

        // recount weights
        foreach (array_keys($this->dictionary) as $token) {
            if (1 == $this->dictionary[$token]['weight']) {
                $this->dictionary[$token]['weight'] = $this->dictionary[$token]['count'] / $this->usableTokenCount;
            }
        }
    }

    /**
     * Dumps tokens
     * @return array
     */
    public function dump()
    {
        return $this->dictionary;
    }

    public function serialize()
    {
        return serialize(array('dic' => $this->dictionary,
            'document_count' => $this->getDocumentCount(),
            'use_document_count' => $this->useDocumentCount,
            'minimal_token_length' => $this->getMinimalTokenLength(),
            'maximal_token_length' => $this->getMaximalTokenLength()));
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->dictionary = $data['dic'];
        $this->setDocumentCount($data['document_count']);
        $this->useDocumentCount($data['use_document_count']);
        $this->setMinimalTokenLength($data['minimal_token_length']);
        $this->setMaximalTokenLength($data['maximal_token_length']);

        $this->recount();
    }

    /**
     * Matches disctionary against tokens
     * @param TokenArray $tokens
     * @return double
     */
    public function match(TokenArray $tokens)
    {
        $poly = array();

        foreach ($tokens as $token => $count) {

            if(isset($this->dictionary[$token])) {
                $weight = $this->dictionary[$token]['weight'];

                if ($weight != 0) {
                    $poly[] = log(1 - $weight, M_E);
                }
            }
        }

        return 1 / ( 1 + pow(M_E, array_sum($poly)));
    }

    /**
     * Gets document count
     * @return integer
     */
    public function getDocumentCount()
    {
        return $this->documentCount;
    }

    /**
     * Sets document count
     * @param integer $documentCount
     */
    public function setDocumentCount($documentCount)
    {
        $this->documentCount = $documentCount;
    }

    /**
     * Gets minimal frequency in document for token to be included.
     * For example, if this is 0.1, then all tokens found in less than 1 doc of
     * 10 will be not taken in account
     * @return double
     */
    public function getMinimalFrequencyInDocuments()
    {
        return $this->minimalFrequencyInDocuments;
    }

    /**
     * Sets minimal frequency in document for token to be included.
     * For example, if this is 0.1, then all tokens found in less than 1 doc of
     * 10 will be not taken in account
     * @var double $minimalFrequencyInDocuments
     */
    public function setMinimalFrequencyInDocuments($minimalFrequencyInDocuments)
    {
        $this->minimalFrequencyInDocuments = $minimalFrequencyInDocuments;
    }

    /**
     * Sets / gets flag indicating usage of $minimalFrequencyInDocuments
     * @param boolean $use
     * @return boolean
     */
    public function useDocumentCount($use = null)
    {
        if(is_bool($use)) {
            $this->useDocumentCount = $use;
        } else {
            return $this->useDocumentCount;
        }
    }

    /**
     * Gets minimal token length
     * @return integer
     */
    public function getMinimalTokenLength()
    {
        return $this->minimalTokenLength;
    }

    /**
     * Sets minimal token length
     * @param integer $minimalTokenLength
     */
    public function setMinimalTokenLength($minimalTokenLength)
    {
        $this->minimalTokenLength = $minimalTokenLength;
    }

    /**
     * Gets maximal token length
     * @return integer
     */
    public function getMaximalTokenLength()
    {
        return $this->maximalTokenLength;
    }

    /**
     * Sets maximal token length
     * @param integer $maximalTokenLength
     */
    public function setMaximalTokenLength($maximalTokenLength)
    {
        $this->maximalTokenLength = $maximalTokenLength;
    }

    /**
     * Gets total token count
     * @return integer
     */
    public function getTokenCount()
    {
        return $this->tokenCount;
    }

    /**
     * Gets usable token count (not filtered by length, stemmer and so on)
     * @return integer
     */
    public function getUsableTokenCount()
    {
        return $this->usableTokenCount;
    }


}
