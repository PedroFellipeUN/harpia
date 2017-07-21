<?php

namespace Modulos\Integracao\Listeners;

use Moodle;
use GuzzleHttp\Exception\ConnectException;
use Modulos\Integracao\Events\SincronizacaoEvent;
use Modulos\Integracao\Events\AtualizarSyncEvent;
use Modulos\Academico\Repositories\CursoRepository;
use Modulos\Academico\Repositories\TurmaRepository;
use Modulos\Academico\Repositories\PeriodoLetivoRepository;
use Modulos\Integracao\Repositories\AmbienteVirtualRepository;

class SincronizacaoListener
{
    private $turmaRepository;
    private $cursoRepository;
    private $periodoLetivoRepository;
    private $ambienteVirtualRepository;

    public function __construct(TurmaRepository $turmaRepository,
                                AmbienteVirtualRepository $ambienteVirtualRepository,
                                CursoRepository $cursoRepository,
                                PeriodoLetivoRepository $periodoLetivoRepository)
    {
        $this->turmaRepository = $turmaRepository;
        $this->cursoRepository = $cursoRepository;
        $this->periodoLetivoRepository = $periodoLetivoRepository;
        $this->ambienteVirtualRepository = $ambienteVirtualRepository;
    }

    public function handle(SincronizacaoEvent $sincronizacaoEvent)
    {
        try {
            if ($sincronizacaoEvent->getMoodleFunction() == 'local_integracao_create_course') {
                return $this->sincronizarCriacaoTurma($sincronizacaoEvent);
            }
        } catch (ConnectException $exception) {
            flash()->error('Falha ao tentar sincronizar com o ambiente');
            // Interrompe a propagacao do evento
            return false;
        } catch (\Exception $exception) {
            if (config('app.debug')) {
                throw $exception;
            }

            return false;
        }
    }

    /**
     * @param SincronizacaoEvent $sincronizacaoEvent
     * @return bool
     */
    public function sincronizarCriacaoTurma(SincronizacaoEvent $sincronizacaoEvent)
    {
        $turma = $this->turmaRepository->find($sincronizacaoEvent->getData()->sym_table_id);
        $ambiente = $this->ambienteVirtualRepository->getAmbienteByTurma($turma->trm_id);

        if (!$ambiente) {
            return false;
        }

        $data['course']['trm_id'] = $turma->trm_id;
        $data['course']['category'] = 1;
        $data['course']['shortname'] = $this->turmaRepository->shortName($turma);
        $data['course']['fullname'] = $this->turmaRepository->fullName($turma);
        $data['course']['summaryformat'] = 1;
        $data['course']['format'] = 'topics';
        $data['course']['numsections'] = 0;


        $param['url'] = $ambiente->url;
        $param['token'] = $ambiente->token;
        $param['action'] = 'post';
        $param['functioname'] = 'local_integracao_create_course';
        $param['data'] = $data;

        $response = Moodle::send($param);
        $status = 3;

        if (array_key_exists('status', $response) && $response['status'] == 'success') {
            $status = 2;
        }

        event(new AtualizarSyncEvent($turma, $status, $response['message']));
    }
}
