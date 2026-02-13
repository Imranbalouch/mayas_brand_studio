<html>

<body style="background-color:#ffffff; font-family: Open Sans, sans-serif;font-size:100%;font-weight:400;line-height:1.4;color:#000;">
  <table style="max-width:670px;margin:50px auto 10px;background-color:#fff;padding:50px;border-radius:3px;border: 1px solid #000;border-top:solid 10px #000;">
    <thead>
      <tr>
        <th style="text-align:left; padding:50px 15px 0 15px;">
            <img src="{{$details['Logo']}}" alt="{{$details['WebsiteName']}}" />
            <!--{{$details['WebsiteName']}}-->
        </th>
        <th style="text-align:right;font-weight:bold; padding:50px 15px 0 15px;">{{$details['currentDate']}}</th>
      </tr>
    </thead>
    
    <h1>{{$details['heading']}}</h1>
    
    <tbody>
        
            
        <tr>
            
            <td colspan="2" style="border-left:1px solid #000; border-right:1px solid #000; border-top:1px solid #000; border-bottom:1px solid #000; padding:10px 20px;">
              
                  <span style="font-weight:bold;display:inline-block;min-width:146px"> {{$details['SubscriberEmail']}} is your new subscriber </span> 
            
            </td>
            
        </tr>
      

    </tbody>
    <tfooter>
      <tr>
        <td colspan="2" style="font-size:14px;padding:50px 15px 0 15px;">
          <strong style="display:block;margin:0 0 10px 0;">Regards</strong>{{$details['WebsiteName']}}<br> <br>
          <b>Phone:</b> {{$details['companyPhone']}}<br>
          <b>Email:</b>{{$details['companyEmail']}}
        </td>
      </tr>
    </tfooter>
  </table>
</body>

</html>