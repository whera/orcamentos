<?php

namespace Orcamentos\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Orcamentos\Service\Share as ShareService;
use Orcamentos\Model\View as ViewModel;

class ShareController
{

	public function detail(Request $request, Application $app, $hash)
	{	
		if ( !isset($hash) ) {
			throw new Exception("Parâmetros inválidos", 1);
		}
		
		$share = $app['orm.em']->getRepository('Orcamentos\Model\Share')->findOneBy(array('hash'=> $hash));
		
		$shareId = $share->getId();

		if (!$share){
			$app->abort(404, "Compartilhamento não existente");
		}

		$view = new ViewModel();
		$view->setShare($share);

		$app['orm.em']->persist($view);
		$app['orm.em']->flush();

		$quote = $share->getQuote();

		$resourceCollection = $quote->getResourceQuoteCollection();
		
		$shareCollection = $quote->getShareCollection();
		
		$shareNotesCollection = array();

		foreach ($shareCollection as $sc) {
			$notes = $sc->getShareNotesCollection();
			foreach ($notes as $note) {
				$shareNotesCollection[] = $note;
			}
		}

		usort($shareNotesCollection, function ($a, $b)
		{
		    if ($a->getCreated() == $b->getCreated()) {
		        return 0;
		    }
		    return ($a->getCreated()  < $b->getCreated() ) ? 1 : -1;
		});

		$day = date('d');
		$month = date('m');
		$year = date('Y');
		
		$monthName = null;

		switch ($month) {
			case '01':
				$monthName = 'Janeiro';
				break;

			case '02':
				$monthName = 'Fevereiro';
				break;

			case '03':
				$monthName = 'Março';
				break;

			case '04':
				$monthName = 'Abril';
				break;

			case '05':
				$monthName = 'Maio';
				break;

			case '06':
				$monthName = 'Junho';
				break;

			case '07':
				$monthName = 'Julho';
				break;

			case '08':
				$monthName = 'Agosto';
				break;

			case '09':
				$monthName = 'Setembro';
				break;

			case '10':
				$monthName = 'Outubro';
				break;

			case '11':
				$monthName = 'Novembro';
				break;

			case '12':
				$monthName = 'Dezembro';
				break;
		}
		$createdSignature = 'Joinville, ' . $day . ' de ' . $monthName . ' de ' . $year . '.';
		return $app['twig']->render('share/detail.twig',
			array(
				'quote' => $quote,
				'resourceCollection' => $resourceCollection,
				'createdSignature' => $createdSignature,
				'shareNotesCollection' => $shareNotesCollection,
				'shareId' => $shareId
			)
		);
	}


	public function create(Request $request, Application $app)
	{
		$data = $request->request->all();
		$data['companyId'] = $app['session']->get('companyId');

		if ( count($data['email']) > 0 ){
	    	$data = json_encode($data);
			$shareService = new ShareService();
			$shareService->setEm($app['orm.em']);
			$emails = $shareService->save($data);
		}    	

		return json_encode($emails);
	}

	public function comment(Request $request, Application $app)
	{
		$data = $request->request->all();

    	$data = json_encode($data);
		$shareService = new ShareService();
		$shareService->setEm($app['orm.em']);
		$note = $shareService->comment($data);
		$result = array( 'email' => $note->getShare()->getEmail(), 'comment' => $note->getNote(), 'id' => $note->getId());
		return json_encode($result);
	}

	public function delete(Request $request, Application $app, $shareId)
	{	
		$em = $app['orm.em'];
		$share = $em->getRepository('Orcamentos\Model\Share')->find($shareId);
		$em->remove($share);
		$em->flush();
		return true;
	}

	public function resend(Request $request, Application $app)
	{	
		$data = $request->request->all();
    	$data = json_encode($data);
		$shareService = new ShareService();
		$shareService->setEm($app['orm.em']);
		$email = $shareService->resend($data);
		
		return json_encode($email);
	}

	public function sendEmails(Request $request, Application $app, $limit)
	{	
		$shareService = new ShareService();
		$result = $shareService->sendEmails($limit, $app);
		
		return json_encode($result);
	}

	public function removeComment(Request $request, Application $app)
	{
		$em = $app['orm.em'];
		$shareNoteId = $request->get('shareNoteId');

		if(!isset($shareNoteId)){
			throw new Exception("Invalid parameters", 1);
		}

		$noteId = $shareNoteId;

    	$data = json_encode(array('noteId' => $noteId));
		$shareService = new ShareService();
		$shareService->setEm($app['orm.em']);
		$note = $shareService->removeComment($data);

		return $app->redirect($_SERVER['HTTP_REFERER']);
	}
}