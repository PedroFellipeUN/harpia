@extends('layouts.modulos.matriculas')

@section('stylesheets')
    <link rel="stylesheet" href="{{asset('/css/plugins/select2.css')}}">
@endsection

@section('title')
    Módulo de Matrículas
@stop

@section('subtitle')
    Inscritos
@stop

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-filter"></i> Filtrar dados</h3>

            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                </button>
            </div>
            <!-- /.box-tools -->
        </div>
        <!-- /.box-header -->
        <div class="box-body">
            <div class="row">
                <form method="GET" action="{{ route('matriculas.index.inscricoes.index', $seletivo->id) }}">
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="nome" id="nome" value="{{Request::input('nome')}}"
                               placeholder="Nome">
                    </div>
                    <div class="col-md-2">
                        <input type="text" class="form-control" name="email" id="email"
                               value="{{Request::input('email')}}" placeholder="E-mail">
                    </div>
                    <div class="col-md-2">
                        <input type="text" class="form-control" name="cpf" id="cpf" value="{{Request::input('cpf')}}"
                               placeholder="CPF">
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <select class="form-control custom-select" name="status">
                                <option value="">Status</option>
                                <option value="completo" @if (Request::get('status') == 'completo') selected @endif>
                                    Completo
                                </option>
                                <option value="classificado"
                                        @if (Request::get('status') == 'classificado') selected @endif>Classificado
                                </option>
                                <option value="deferido" @if (Request::get('status') == 'deferido') selected @endif>
                                    Deferido
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <input type="submit" class="form-control btn-primary" value="Buscar">
                    </div>
                </form>
            </div>
        </div>
        <!-- /.box-body -->
    </div>
    @if(!is_null($tabela))
        <div class="box box-primary">
            <div class="box-header">
                <div class="row">
                    <div class="form-group col-md-6 @if ($errors->has('chamada_id')) has-error @endif">
                        {!! Form::label('chamada_id', 'Chamada*', ['class' => 'control-label']) !!}
                        <div class="controls">
                            {!! Form::select('chamada_id', $chamadas, old('chamada_id'), ['class' => 'form-control', 'placeholder' => 'Escolha a Chamada', 'id' => 'chamada_id']) !!}
                            @if ($errors->has('chamada_id')) <p class="help-block">{{ $errors->first('chamada_id') }}</p> @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="box box-primary">
            <div class="box-header inscritos">
                {!! $tabela->render() !!}
            </div>
            <div class="box-footer">
                <div class="col-md-12">
                    <button class="btn btn-success btnChamada">Criar lista de chamada</button>
                </div>
            </div>
        </div>

        <div class="text-center">{!! $paginacao->links() !!}</div>

    @else
        <div class="box box-primary">
            <div class="box-body">Sem registros para apresentar</div>
        </div>
    @endif
@stop

@section('scripts')
    <script src="{{url('/')}}/js/plugins/select2.js"></script>
    <script type="text/javascript">
        $(function () {
            $('select').select2();

            var token = "{{csrf_token()}}";
            $('.inscritos table tr:first th:first').html("<label><input id='select_all' type='checkbox'></label>");

            $('.inscritos').on('click', '#select_all', function (event) {
                if (this.checked) {
                    $('.matriculas').each(function () {
                        this.checked = true;
                    });
                } else {
                    $('.matriculas').each(function () {
                        this.checked = false;
                    });
                }

                // var hiddenButton = function () {
                //     var checkboxes = $('.table table td input[type="checkbox"]');
                //
                //     if (checkboxes.is(':checked')) {
                //         $(document).find('.btnChamada').removeClass('hidden');
                //     } else {
                //         $(document).find('.btnChamada').addClass('hidden');
                //     }
                // };
                //
                //
                // $(document).on('click', '.table input[type="checkbox"]', hiddenButton);



            });

            var userIds = new Array();

            $('.matriculas:checked').each(function () {
                userIds.push($(this).val());
            });

            $('.btnChamada').on('click',function () {
                var quant = $('.matriculas:checked').length;

                var chamdaId = $('#chamada_id').val();

                if ((!(quant > 0)) || (!chamdaId || chamdaId === '')) {
                    return false;
                }

                var userIds = new Array();

                $('.matriculas:checked').each(function () {
                    userIds.push($(this).val());
                });

                sendChamadas(chamdaId, userIds);
            });

            var sendChamadas = function (chamdaId, userIds) {

                var dados = {
                    users: userIds,
                    chamada_id: chamdaId,
                    _token: token
                };

                $.harpia.showloading();

                url = '{{ route('matriculas.listachamadas.create') }}';

                $.ajax({
                    type: 'POST',
                    url: url,
                    data: dados,
                    success: function (data) {
                        $.harpia.hideloading();
                        toastr.success('Chamada criada com sucesso!', null, {progressBar: true});

                        setTimeout(function () {
                            window.location.href = "{{ route('matriculas.index.index')}}";
                        }, 2000);

                        // var turma = turmaSelect.val();
                        // var ofertaDisciplina = disciplinasOfertadasSelect.val();
                        // var polo = poloSelect.val();
                        //
                        // var parameters = {
                        //     trm_id: turma,
                        //     ofd_id: ofertaDisciplina
                        // };
                        // if (polo && polo != '') {
                        //
                        //     parameters['pol_id'] = polo;
                        // }
                        //
                        // renderTable(parameters);
                    },
                    error: function (xhr, textStatus, error) {
                        $.harpia.hideloading();

                        switch (xhr.status) {
                            case 400:
                                toastr.error(xhr.responseText.replace(/\"/g, ''), null, {progressBar: true});
                                break;
                            default:
                                toastr.error(xhr.responseText.replace(/\"/g, ''), null, {progressBar: true});
                        }
                    }
                });
            };
        });
    </script>
@endsection
