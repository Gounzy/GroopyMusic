<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Select2Controller extends Controller
{
    protected $container;
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @Route("/genres", name="select2_genres")
     */
    public function genresAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $q = $request->get('q');
        $genres = $em->getRepository('AppBundle:Genre')->findForString($q, $request->getLocale());

        $genresArray = [];

        foreach($genres as $genre)
        {
            $genresArray[] = array(
                'id' => $genre->getId(),
                'text' => $genre->getName(),
            );
        }
        return new Response(json_encode($genresArray), 200, array('Content-Type' => 'application/json'));
    }

    /**
     * @Route("/provinces", name="select2_provinces")
     */
    public function provincesAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $q = $request->get('q');
        $provinces = $em->getRepository('AppBundle:Province')->findForString($q, $request->getLocale());

        $provincesArray = [];

        foreach($provinces as $province)
        {
            $genresArray[] = array(
                'id' => $province->getId(),
                'text' => $province->getName(),
            );
        }
        return new Response(json_encode($provincesArray), 200, array('Content-Type' => 'application/json'));
    }

    /**
     * @Route("/artists", name="select2_artists")
     */
    public function artistsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $artists = $em->getRepository('AppBundle:Artist')->findNotDeletedBy(null);

        $artistsArray = [];

        foreach($artists as $artist)
        {
            $artistsArray[] = array(
                'id' => $artist->getId(),
                'text' => $artist->getArtistname(),
            );
        }
        return new Response(json_encode($artistsArray), 200, array('Content-Type' => 'application/json'));
    }

}
