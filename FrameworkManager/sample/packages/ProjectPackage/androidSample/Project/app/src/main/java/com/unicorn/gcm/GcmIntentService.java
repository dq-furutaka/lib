package com.unicorn.gcm;

import android.app.Activity;
import android.app.ActivityManager;
import android.app.ActivityManager.RunningAppProcessInfo;
import android.app.IntentService;
import android.content.Context;
import android.content.Intent;
import android.os.Bundle;

import com.google.android.gms.gcm.GoogleCloudMessaging;

import java.util.List;

public class GcmIntentService extends IntentService {
	public static final String TAG = "GcmIntentService";

	public GcmIntentService() {
		super("GcmIntentService");
	}

	@Override
	protected void onHandleIntent(Intent intent) {
		Bundle extras = intent.getExtras();
		GoogleCloudMessaging gcm = GoogleCloudMessaging.getInstance(this);
		String messageType = gcm.getMessageType(intent);

//		if (!extras.isEmpty()) {
//			if (GoogleCloudMessaging.MESSAGE_TYPE_SEND_ERROR.equals(messageType)) {
//				// 何もしない
//				Log.d(TAG, "MESSAGE_TYPE_SEND_ERROR:".concat(extras.toString()));
//			} else if (GoogleCloudMessaging.MESSAGE_TYPE_DELETED.equals(messageType)) {
//				// 何もしない
//				Log.d(TAG, "MESSAGE_TYPE_DELETED:".concat(extras.toString()));
//			} else if (GoogleCloudMessaging.MESSAGE_TYPE_MESSAGE.equals(messageType)) {
//				Log.i(TAG, "Received: " + extras.toString());
//				String alertMessage = extras.getString("alert", "");
//				String chatScheme = extras.getString("scm", "");
//				// 通知設定確認して
//				GmatchNotification.sendNotification(this.getApplicationContext(), alertMessage);
//				if (isForegroundApp(App.getInstance().getApplicationContext())) {
//					if (!chatScheme.isEmpty()) {
//						Log.d("ChatReload --send--");
//						DataManager.getInstance()
//								.sendBroadCast(Constant.BROADCAST_CHAT, chatScheme);
//					}
//
//					Log.d("ModelReload --send--");
//					LocalBroadcastManager manager = LocalBroadcastManager.getInstance(App
//							.getInstance().getApplicationContext());
//					Intent reloadIntent = new Intent(Constant.ACTION_MODELRELOAD);
//					manager.sendBroadcast(reloadIntent);
//				}
//			}
//		}
		GcmBroadcastReceiver.completeWakefulIntent(intent);
	}

	private Boolean isForegroundApp(Context con) {
		// 実行中のプロセス一覧を取得
		ActivityManager activityManager = ((ActivityManager) con
				.getSystemService(Activity.ACTIVITY_SERVICE));
		List<RunningAppProcessInfo> processInfoList = activityManager.getRunningAppProcesses();
		for (RunningAppProcessInfo info : processInfoList) {
			if (info.processName.equals(con.getPackageName())) {
				if (info.importance == RunningAppProcessInfo.IMPORTANCE_FOREGROUND) {
					return true;
				}
				return false;
			}
		}
		return false;
	}
}
