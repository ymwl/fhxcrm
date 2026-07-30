<?php
declare(strict_types=1);

namespace app\exception;

use think\exception\Handle;
use think\facade\Db;
use think\Request;
use think\Response;
use Throwable;

class FhxExceptionHandle extends Handle
{

    /**
     * 异常处理入口：自动回滚未提交的数据库事务
     * @access public
     * @param Request   $request
     * @param Throwable $e
     * @return Response
     */
    public function render(Request $request, Throwable $e): Response
    {
        // 判断是否存在活跃的数据库事务，若有则自动回滚，确保数据一致性
        try {
            Db::rollback();
        } catch (\Throwable $throwable) {
            // 无活跃事务或数据库连接异常时忽略，不影响原有异常处理流程
        }

        return parent::render($request, $e);
    }

    /**
     * 异常日志记录：所有模式下均记录完整异常信息（含 trace）到文件日志
     * @access public
     * @param Throwable $exception
     * @return void
     */
    public function report(Throwable $exception): void
    {
        if (!$this->isIgnoreReport($exception)) {
            $data = [
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'message' => $this->getMessage($exception),
                'code'    => $this->getCode($exception),
            ];
            $log = "[{$data['code']}]{$data['message']}[{$data['file']}:{$data['line']}]";
            $log .= PHP_EOL . $exception->getTraceAsString();

            try {
                $this->app->log->record($log, 'error');
            } catch (\Exception $e) {
            }
        }
    }

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

    /**
     * 收集部署模式异常数据：不向前端返回 trace 等敏感信息
     * @access protected
     * @param Throwable $exception
     * @return array
     */
    protected function getDeployMsg(Throwable $exception): array
    {
        $showErrorMsg = $this->isShowErrorMsg($exception);
        if ($showErrorMsg || $this->app->config->get('app.show_error_msg', false)) {
            $message = $this->getMessage($exception);
        } else {
            $message = $this->app->config->get('app.error_message');
        }

        return [
            'code'    => $this->getCode($exception),
            'message' => $message,
        ];
    }
}