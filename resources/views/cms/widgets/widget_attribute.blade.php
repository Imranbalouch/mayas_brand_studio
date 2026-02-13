<form action="" method="post" id="shortcodeForm">
    @foreach ($widgetFields as $name => $widgetField)
        <div class="col-12 mb-3">
            <label for="{{strtolower($widgetField->field_name)}}" class="form-label">{{ucfirst(str_replace('_',' ',$widgetField->field_name))}}</label>
            @if ($widgetField->field_type == 'text')
                <input type="text" name="{{$widgetField->field_id}}" class="form-control" id="{{$widgetField->field_id}}">
            @elseif ($widgetField->field_type == 'module')
                <input type="text" name="{{$widgetField->field_id}}" value="{{$widgetField->field_id}}" readonly class="form-control" id="{{$widgetField->field_id}}">
            @elseif ($widgetField->field_type == 'image')
                <input type="file" name="{{str_replace('','_',strtolower($widgetField->field_name))}}" onchange="uploadImage(event)" class="form-control" id="{{str_replace('','_',strtolower($widgetField->field_name))}}">
                <input type="hidden" name="{{$widgetField->field_id}}" class="form-control" id="{{$widgetField->field_id}}">
            @elseif ($widgetField->field_type == 'textarea')
                <textarea name="{{$widgetField->field_id}}" class="form-control" id="{{$widgetField->field_id}}"></textarea>
            @endif
        </div>
    @endforeach
    <div class="col-12">
      <button type="submit" class="mt-2 btn btn-primary">Submit</button>
    </div>
</form>