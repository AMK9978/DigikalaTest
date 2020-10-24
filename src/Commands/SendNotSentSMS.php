<?php


namespace App\Commands;


use App\Repository\SMSLogRepository;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Request;


class SendNotSentSMS extends Command
{
    private $container;
    private $smsRepo;
    protected static $defaultName = 'app:sms:send';

    public function __construct(ContainerInterface $container, SMSLogRepository $smsRepo)
    {
        $this->container = $container;
        $this->smsRepo = $smsRepo;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $not_sent_sms = $this->smsRepo->getNotSentSMS();
        foreach ($not_sent_sms as $id) {
            var_dump("id". $id);
            $request = new Request();
            $request->request->set("sms_id", $id);
            $this->container->get('SMSControllerService')->sendSMS($request);
        }
        $io->success(sprintf('Successfully sent'));

        return 0;
    }
}