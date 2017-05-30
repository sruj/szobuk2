<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Form\Filter\StatusType;
use AppBundle\Form\OrderType;
use AppBundle\Entity\Status;
use AppBundle\Exception\OrderNotFoundException;

class OrderPanelController extends Controller
{

    /**
     * @Route("/panel/order-details/{orderid}/{userid}/", name="order_panel")
     * @Route("/panel/order-details/{orderid}/", defaults={"orderid": false}, name="manager_order_panel")
     */
    public function detailsAction(Request $request, $orderid = false, $userid = false)
    {
        $loggedUserId = $this->getUser()->getId();
        $adminLogged = $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN');
        if (!(($loggedUserId == $userid) or ($adminLogged))) {
            throw $this->createAccessDeniedException();
        }

        if ($adminLogged) {
            $orderRepo = $this->getDoctrine()
                ->getRepository('Order.php')
                ->findoneBy(array('idorder' => $orderid));
            if (!$orderRepo) {
                throw new OrderNotFoundException('Nie ma w bazie danych szukanego zamówienia.');
            }
        }

        if (!$adminLogged) {
            $orderRepo = $this->getDoctrine()
                ->getRepository('Order.php')
                ->findoneBy(array('idorder' => $orderid, 'idklient' => $userid));
            if (!$orderRepo) {
                throw $this->createAccessDeniedException();
            }
        }

        $products = $this->getDoctrine()
            ->getRepository('OrderProduct.php')
            ->findBy(array('idorder' => $orderid));

        $statusForm = $this->createForm(OrderType::class, $orderRepo)->add('zmień status', 'submit');
        $statusForm->handleRequest($request);
        if ($statusForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->merge($statusForm->getData());
            $em->flush();
        }

        return $this->render('AppBundle:OrderPanel:details.html.twig', [
            'order' => $orderRepo, 'products' => $products,
            'statusForm' => $statusForm->createView()]);
    }
}
    