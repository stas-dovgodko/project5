<?php
namespace project5\Stream;

interface ITransformer {

    /**
     * @param IStreamable $source
     * @return IStreamable
     */
    public function transform(IStreamable $source);
}