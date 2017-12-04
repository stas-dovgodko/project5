<?php
namespace project5\Form\Openselect;

use project5\IProvider;
use project5\Provider\ICanBeCounted;
use project5\Provider\ICanBePaged;
use project5\Provider\IEntity;
use project5\Web\Request;

class Controller
{
    public function handle(Request $request, IProvider $provider, $field = null)
    {
        $data = ['results' => []];

        if ($provider instanceof ICanBePaged) {
            $page = $request->getParam('page', 1);
            $per_page = $request->getParam('page_limit', 10);

            $provider->setPagerOffset(($page - 1)*$per_page);
            $provider->setPagerLimit($per_page);
            $data['more'] = $provider->hasNextPage();
        }


        if (($provider instanceof ICanBeCounted) && $provider->canBeCounted()) {
            $data['total'] = $provider->getCount();

        }

        $dump = function (IEntity $item) {
            return (string)$item;
        };

        if ($field) {
            foreach ($provider->getFields() as $f) {
                if ($f->getName() === $field) {
                    $dump = function (IEntity $item) use ($f) {
                        return $f->get($item);
                    };
                    break;
                }
            }
        }

        foreach ($provider->iterate() as $object) {
            /** @var $object IEntity */
            $data['results'][] = [
                'id' => json_encode($object->getUid()),
                'text' => (string)$dump($object)
            ];
        }

        return $data;
    }
}