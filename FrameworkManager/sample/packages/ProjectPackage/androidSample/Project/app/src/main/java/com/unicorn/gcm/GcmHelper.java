package com.unicorn.gcm;

import java.io.IOException;

import android.app.Activity;
import android.app.Dialog;
import android.content.Context;
import android.content.DialogInterface;
import android.content.DialogInterface.OnCancelListener;
import android.os.AsyncTask;
import android.util.Log;

import com.google.android.gms.common.ConnectionResult;
import com.google.android.gms.common.GooglePlayServicesUtil;
import com.google.android.gms.gcm.GoogleCloudMessaging;

/**
 * GCM処理
 * 
 * @author c1718
 *
 */
public class GcmHelper {

	private final static int PLAY_SERVICES_RESOLUTION_REQUEST = 9000;

	private Context context;
	private GcmPreferences prefs;

	/**
	 * @param ctx コンテキスト
	 */
	public GcmHelper(Context ctx) {
		this.context = ctx;
		this.prefs = new GcmPreferences(ctx);
	}

	/**
	 * GooglePlayServiceが使用可能か調べる
	 * 
	 * @param activity アクティビティ
	 * @return GooglePlayServiceが使用可能な場合は{@code true}
	 */
	public boolean checkPlayServices(Activity activity) {
		int resultCode = GooglePlayServicesUtil.isGooglePlayServicesAvailable(this.context);
		if (resultCode == ConnectionResult.SUCCESS) {
			Log.d(getClass().getName(), "GooglePlayServices is available.");
			return true;
		}
		if (GooglePlayServicesUtil.isUserRecoverableError(resultCode)) {
			Log.d(getClass().getName(), "GooglePlayServices is userRecoverableError.");
			// ユーザーがリカバリー可能なエラー
			Dialog errorDialog = GooglePlayServicesUtil.getErrorDialog(resultCode, activity,
					PLAY_SERVICES_RESOLUTION_REQUEST, new OnCancelListener() {

						@Override
						public void onCancel(DialogInterface dialog) {
							Log.d(getClass().getName(), "canceled");
							dialog.dismiss();
						}
					});
			errorDialog.show();
		} else {
			Log.i(getClass().getName(), "This device is not supported.");
		}
		return false;
	}

	/**
	 * RegistrationIDが保存されているか調べる
	 * 
	 * @return RegistrationIDがローカルに保存されている場合は{@code true}
	 */
	public boolean existsRegistrationId() {
		String registrationId = prefs.getRegistrationId();
		if (registrationId.isEmpty()) {
			Log.d(getClass().getName(), "Not exists registrationId.");
			return false;
		}
		Log.d(getClass().getName(), "Exists registrationId:" + registrationId);
		return true;
	}

	/**
	 * GCMに対してRegistrationID登録し、RegistrationIDをローカルに保存します
	 * 
	 * @param senderId SenderID
	 */
	public void register(final String senderId) {
		new AsyncTask<Void, Void, String>() {

			@Override
			protected String doInBackground(Void... params) {
				GoogleCloudMessaging gcm = GoogleCloudMessaging.getInstance(context);
				try {
					return gcm.register(senderId);
				} catch (IOException e) {
					Log.w(getClass().getName(), "", e);
					return "";
				}
			}

			@Override
			protected void onPostExecute(String registrationId) {
				Log.d(getClass().getName(), "GCM registrationId:" + registrationId);
				if (!registrationId.isEmpty()) {
					prefs.setRegistrationId(registrationId);
				}
			}
		}.execute(null, null, null);
	}
}
