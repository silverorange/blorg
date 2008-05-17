create or replace view BlorgPostReplyCountView as
	select post, count(id) as reply_count, max(createdate) as last_reply_date
		from BlorgReply
		where status = 1 and spam = false -- status 1 is published
		group by post;

