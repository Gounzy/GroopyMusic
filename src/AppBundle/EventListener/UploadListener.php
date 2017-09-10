<?php

namespace AppBundle\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Oneup\UploaderBundle\Event\PostPersistEvent;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadListener
{
    /**
     * @var ObjectManager
     */
    private $om;

    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
    }

    public function onUpload(PostPersistEvent $event)
    {
        $request = $event->getRequest();

        $photo = new \AppBundle\Entity\Photo();
        /** @var UploadedFile $file */
        $file = $event->getFile();
        $photo->setFilename($file->getFilename());

        if($artist_id = $request->get('artist')) {
            $pp = boolval($request->get('pp', 0));
            $artist = $this->om->getRepository('AppBundle:Artist')->find($artist_id);

            if ($pp) {
                $artist->setProfilepic($photo);
            } else {
                $artist->addPhoto($photo);
            }
            $this->om->persist($artist);
        }

        elseif($hall_id = $request->get('hall')) {
            $hall = $this->om->getRepository('AppBundle:Hall')->find($hall_id);
            $hall->addPhoto($photo);
            $this->om->persist($hall);
        }
        $this->om->flush();

        $response = $event->getResponse();
        $response['success'] = true;
        $response['newfilename'] = $photo->getFilename();
        return $response;
    }


}