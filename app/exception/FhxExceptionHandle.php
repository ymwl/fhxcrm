<?php
declare(strict_types=1);

namespace app\exception;

use think\exception\Handle;
use Throwable;

class FhxExceptionHandle extends Handle
{

    /**
     * 收集调试模式异常数据
     * @access protected
     * @param Throwable $exception
     * @return array
     */
    protected function getDebugMsg(Throwable $exception): array
    {
        // 调试模式，获取详细的错误信息
        $traces        = [];
        $nextException = $exception;

        do {
            $traces[] = [
                'name'    => $nextException::class,
                'file'    => $nextException->getFile(),
                'line'    => $nextException->getLine(),
                'code'    => $this->getCode($nextException),
                'message' => $this->getMessage($nextException),
                'trace'   => $nextException->getTrace(),
                'source'  => $this->getSourceCode($nextException),
            ];
        } while ($nextException = $nextException->getPrevious());

        return [
            'code'    => $this->getCode($exception),
            'msg' => $this->getMessage($exception),
            'traces'  => $traces,
            /*'datas'   => $this->getExtendData($exception),
            'tables'  => [
                'GET Data'            => $this->app->request->get(),
                'POST Data'           => $this->app->request->post(),
                'Files'               => $this->app->request->file(),
                'Cookies'             => $this->app->request->cookie(),
                'Session'             => $this->app->exists('session') ? $this->app->session->all() : [],
                'Server/Request Data' => $this->app->request->server(),
            ],*/
        ];
    }
}