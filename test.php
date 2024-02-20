C:\Program Files\RabbitMQ Server\rabbitmq_server-3.12.12\sbin>
D:\project\rabbit>


你的json要傳給rabbitMQ的一個佇列queue 
queue也要先訂好名稱
小巴會去監聽queue 
所以需要訂好格式他才知道
queue接到的資訊後續該怎麼處理

如果沒有設計成 每一次請求都新增不同的資料夾 的情況：
-使用者id
-檔案名稱
-請求id
-圖片筆數
-路徑

資料夾命名 如果可以區分user跟請求id的情況：
-檔案名稱
-圖片筆數
-路徑



// 資料夾區分使用者user
{
  "requestId": "123456",
  "userId": "78910",
  "imageCount": 3,
  "filePath": "/images/78910/",
  "fileName": [ "image1.jpg", "image2.jpg", "image3.jpg" ]
}


// 資料夾區分使用者user, 請求request
{
  "requestId": "123456",
  "imageCount": 2,
  "filePath": "/images/78910/123456/",
  "fileName": [ "image1.jpg", "image2.jpg" ]
}

//這是他回傳的初版會長這樣
{
  "requestId": "123456",
  "userId": "78910",
  "successCount": 3,
  "failedCount": 0,
  "csvPath": "/images/78910/",
  "csvName":  "20240219_78910_123456_Image2Tag.csv" 
}